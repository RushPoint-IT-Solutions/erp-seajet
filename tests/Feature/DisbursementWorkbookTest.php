<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Erp\ModuleController;
use App\Services\Reports\XlsxReportExporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DisbursementWorkbookTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('code')->nullable();
        });
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->default(1);
            $table->string('bank_name');
            $table->string('account_no');
            $table->string('currency')->default('PHP');
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->default(1);
            $table->integer('supplier_id')->default(1);
            $table->integer('bank_account_id')->default(1);
            $table->string('payment_date')->default('2026-10-01');
            $table->string('payment_no')->default('CV-1');
            $table->string('check_no')->default('000471072');
            $table->decimal('amount', 18, 2)->default(6600);
            $table->string('currency')->default('PHP');
            $table->string('payment_method')->default('Cheque');
            $table->text('voucher_description')->nullable();
            $table->string('bill_no')->nullable();
        });
        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->default(1);
            $table->integer('supplier_id')->default(1);
            $table->string('document_no');
            $table->text('memo')->nullable();
            $table->text('remarks')->nullable();
        });
        DB::table('companies')->insert(['id' => 1]);
        DB::table('suppliers')->insert(['id' => 1, 'name' => 'Vendor']);
        DB::table('bank_accounts')->insert([
            ['id' => 1, 'bank_name' => 'BDO', 'account_no' => '111'],
            ['id' => 2, 'bank_name' => 'Metrobank', 'account_no' => '222'],
        ]);
    }

    public function test_bank_and_period_filter_preserves_check_and_description()
    {
        foreach ([
            ['voucher_description' => 'Pest control'],
            ['bank_account_id' => 2, 'voucher_description' => 'Other bank'],
            ['company_id' => 2, 'voucher_description' => 'Other company'],
            ['payment_date' => '2026-09-01', 'voucher_description' => 'Other month'],
        ] as $payment) {
            DB::table('payments')->insert($payment);
        }
        request()->merge(['bank_account_id' => '1']);
        $rows = $this->rows();
        $this->assertCount(1, $rows);
        $this->assertSame('000471072', $rows[0]['CHECK#']);
        $this->assertSame('Pest control', $rows[0]['PARTICULARS']);
        $this->assertEquals(6600, $rows[0]['CASH IN BANK- PESO']);

        $path = app(XlsxReportExporter::class)->create([[
            'columns' => array_keys($rows[0]), 'rows' => $rows,
        ]]);
        try {
            $zip = new \ZipArchive();
            $zip->open($path);
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            $this->assertContains('t="inlineStr"><is><t xml:space="preserve">000471072</t>', $xml);
            $this->assertContains('Pest control', $xml);
            $this->assertNotContains('Other bank', $xml);
        } finally {
            unlink($path);
        }
    }

    public function test_all_banks_and_vendor_bill_memo_fallback()
    {
        DB::table('purchase_documents')->insert(['document_no' => 'BILL-1', 'memo' => 'Freight charges']);
        DB::table('payments')->insert([
            ['bank_account_id' => 1, 'bill_no' => 'BILL-1'],
            ['bank_account_id' => 2, 'bill_no' => null],
        ]);
        $rows = $this->rows();
        $this->assertCount(2, $rows);
        $this->assertSame('Freight charges', $rows[0]['PARTICULARS']);
        $this->assertSame('', $rows[1]['PARTICULARS']);
    }

    public function test_multiple_bank_accounts_include_both_currencies_and_exclude_other_banks()
    {
        DB::table('bank_accounts')->insert(['id' => 3, 'bank_name' => 'BDO', 'account_no' => '333', 'currency' => 'USD']);
        DB::table('payments')->insert([
            ['bank_account_id' => 1, 'currency' => 'PHP'],
            ['bank_account_id' => 2, 'currency' => 'PHP'],
            ['bank_account_id' => 3, 'currency' => 'USD'],
        ]);
        request()->merge(['bank_account_ids' => ['1', '3']]);
        $rows = $this->rows();
        $this->assertCount(2, $rows);
        $this->assertEquals(6600, array_sum(array_column($rows, 'CASH IN BANK- PESO')));
        $this->assertEquals(6600, array_sum(array_column($rows, 'CASH IN BANK-USD')));
    }

    public function test_invalid_bank_selection_is_rejected()
    {
        request()->merge(['bank_account_ids' => ['1', '999']]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->rows();
    }

    private function rows()
    {
        $method = new \ReflectionMethod(ModuleController::class, 'disbursementWorkbookRows');
        $method->setAccessible(true);
        return $method->invoke(app(ModuleController::class), '2026-10');
    }
}
