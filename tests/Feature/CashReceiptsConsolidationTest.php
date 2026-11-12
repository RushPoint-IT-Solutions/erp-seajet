<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Erp\ModuleController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashReceiptsConsolidationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('companies', function (Blueprint $table) { $table->increments('id'); });
        DB::table('companies')->insert(['id' => 1]);
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->default(1);
            $table->integer('customer_id')->default(1);
            $table->string('class_code')->default('MANILA');
            $table->string('receipt_date')->default('2026-10-01');
            $table->string('receipt_no');
            $table->string('receipt_type')->default('collection_receipt');
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->default('Cash');
            $table->integer('bank_account_id')->nullable();
            $table->string('currency')->default('PHP');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->decimal('amount', 18, 4);
            $table->decimal('cwt_amount', 18, 4)->default(0);
            $table->string('status')->default('posted');
        });
        Schema::create('sales_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('document_no');
            $table->string('soa_no')->nullable();
            $table->string('template_code')->default('BI');
        });
        Schema::create('receipt_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('receipt_id');
            $table->integer('sales_document_id');
            $table->string('component')->nullable();
            $table->decimal('amount_applied', 18, 4)->default(0);
        });
        DB::table('customers')->insert(['id' => 1, 'name' => 'FIRMA']);
    }

    public function test_keeps_same_date_receipts_separate_without_multiplying_cash()
    {
        DB::table('receipts')->insert([
            ['id' => 1, 'receipt_no' => 'OR-6234', 'receipt_type' => 'collection_receipt', 'amount' => 34771.93],
            ['id' => 2, 'receipt_no' => 'AR-7624', 'receipt_type' => 'acknowledgement_receipt', 'amount' => 9969.33],
        ]);
        DB::table('sales_documents')->insert([
            ['id' => 1, 'document_no' => 'BI-197', 'soa_no' => 'SOA-197'],
            ['id' => 2, 'document_no' => 'BI-198', 'soa_no' => null],
        ]);
        DB::table('receipt_applications')->insert([
            ['receipt_id' => 1, 'sales_document_id' => 1, 'component' => 'bi', 'amount_applied' => 20000],
            ['receipt_id' => 1, 'sales_document_id' => 2, 'component' => 'bi', 'amount_applied' => 14771.93],
            ['receipt_id' => 2, 'sales_document_id' => 1, 'component' => 'soa', 'amount_applied' => 9969.33],
        ]);
        $rows = $this->rows();
        $this->assertCount(2, $rows);
        $this->assertEquals(34771.93, $rows[0]['CASH (PHP)']);
        $this->assertEquals(34771.93, $rows[0]['A/R TRADE']);
        $this->assertEquals(9969.33, $rows[1]['A/R REIMBURSABLES CHARGES']);
        $this->assertSame('OR-6234', $rows[0]['OFFICIAL RECEIPT NUMBER']);
        $this->assertSame('AR-7624', $rows[1]['ACKNOWLEDGEMENT RECEIPT NUMBER']);
        $this->assertSame('BI-197, BI-198', $rows[0]['BILLING INVOICE NUMBER']);
        $this->assertSame('SOA-197', $rows[1]['STATEMENT OF ACCOUNT NUMBER']);
        $this->assertSame(2, DB::table('receipts')->count());
    }

    public function test_separates_references_currencies_customers_dates_and_methods_and_excludes_voids()
    {
        $variants = [[], ['reference_no' => 'CHECK-1'], ['currency' => 'USD'],
            ['customer_id' => 2], ['receipt_date' => '2026-10-02'], ['payment_method' => 'Check'],
            ['status' => 'voided'], ['status' => 'cancelled'],
            ['class_code' => 'CEBU'], ['receipt_date' => '2026-09-01']];
        foreach ($variants as $index => $variant) {
            DB::table('receipts')->insert(array_merge([
                'receipt_no' => 'OR-'.$index, 'amount' => 100,
            ], $variant));
        }
        $rows = $this->rows();
        $this->assertCount(6, $rows);
        $this->assertEquals(500, array_sum(array_column($rows, 'CASH (PHP)')));
        $this->assertEquals(100, array_sum(array_column($rows, 'CASH (USD, PHP EQUIVALENT)')));
    }

    public function test_splits_one_receipt_between_bi_and_soa_and_counts_cwt_once()
    {
        DB::table('receipts')->insert([
            'id' => 1, 'receipt_no' => 'OR-MIXED', 'amount' => 1297510.15, 'cwt_amount' => 20000,
        ]);
        DB::table('sales_documents')->insert([
            'id' => 1, 'document_no' => '0001089', 'soa_no' => '7254',
        ]);
        DB::table('receipt_applications')->insert([
            ['receipt_id' => 1, 'sales_document_id' => 1, 'component' => 'bi', 'amount_applied' => 389309.68],
            ['receipt_id' => 1, 'sales_document_id' => 1, 'component' => 'soa', 'amount_applied' => 928200.47],
        ]);

        $row = $this->rows()[0];
        $this->assertEquals(369309.68, $row['A/R TRADE']);
        $this->assertEquals(928200.47, $row['A/R REIMBURSABLES CHARGES']);
        $this->assertArrayNotHasKey('WITHHOLDING-CWT (2307)', $row);
        $this->assertEquals(1297510.15, $row['CASH (PHP)']);
        $this->assertEquals($row['A/R TRADE'] + $row['A/R REIMBURSABLES CHARGES'],
            $row['CASH (PHP)']);
    }

    public function test_unapplied_acknowledgement_receipt_is_reimbursable()
    {
        DB::table('receipts')->insert([
            'receipt_no' => 'AR-UNAPPLIED', 'receipt_type' => 'acknowledgement_receipt', 'amount' => 250,
        ]);

        $row = $this->rows()[0];
        $this->assertEquals(0, $row['A/R TRADE']);
        $this->assertEquals(250, $row['A/R REIMBURSABLES CHARGES']);
        $this->assertEquals(250, $row['CASH (PHP)']);
    }

    public function test_converts_each_usd_receipt_at_its_own_rate_before_consolidating()
    {
        DB::table('receipts')->insert([
            ['receipt_no' => 'USD-1', 'currency' => 'USD', 'amount' => 100, 'cwt_amount' => 10, 'exchange_rate' => 56],
            ['receipt_no' => 'USD-2', 'currency' => 'USD', 'amount' => 100, 'cwt_amount' => 10, 'exchange_rate' => 57],
        ]);
        $rows = $this->rows();
        $this->assertCount(2, $rows);
        $this->assertEquals(5600, $rows[0]['CASH (USD, PHP EQUIVALENT)']);
        $this->assertEquals(5700, $rows[1]['CASH (USD, PHP EQUIVALENT)']);
        $this->assertEquals(5600, $rows[0]['A/R TRADE']);
        $this->assertEquals(5700, $rows[1]['A/R TRADE']);
    }

    public function test_soa_a_collection_credits_trade_receivable_and_preserves_soa_reference()
    {
        $documentId = DB::table('sales_documents')->insertGetId(['document_no' => 'SOAA-001', 'soa_no' => 'SOAA-001', 'template_code' => 'SOA_A']);
        $receiptId = DB::table('receipts')->insertGetId(['receipt_no' => 'AR-SOAA-001', 'receipt_type' => 'acknowledgement_receipt', 'amount' => 100]);
        DB::table('receipt_applications')->insert(['receipt_id' => $receiptId, 'sales_document_id' => $documentId, 'component' => 'soa', 'amount_applied' => 100]);
        $row = $this->rows()[0];
        $this->assertEquals(100, $row['A/R TRADE']);
        $this->assertEquals(0, $row['A/R REIMBURSABLES CHARGES']);
        $this->assertSame('SOAA-001', $row['STATEMENT OF ACCOUNT NUMBER']);
        $this->assertSame('', $row['BILLING INVOICE NUMBER']);
    }

    private function rows()
    {
        $method = new \ReflectionMethod(ModuleController::class, 'cashReceiptsWorkbookRows');
        $method->setAccessible(true);
        return $method->invoke(app(ModuleController::class), 'MANILA', '2026-10');
    }
}
