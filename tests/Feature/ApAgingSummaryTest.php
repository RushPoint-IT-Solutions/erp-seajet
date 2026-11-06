<?php

namespace Tests\Feature;

use App\Http\Controllers\Erp\ModuleController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApAgingSummaryTest extends TestCase
{
    public function test_summary_matches_details_and_separates_currencies_with_date_filters()
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('companies', function (Blueprint $table) { $table->increments('id'); });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('id'); $table->string('name');
        });
        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->default(1);
            $table->integer('supplier_id')->default(1);
            $table->string('document_no');
            $table->string('document_date');
            $table->string('currency')->default('PHP');
            $table->decimal('exchange_rate', 18, 6)->default(1);
            $table->string('status')->default('posted');
            $table->decimal('grand_total', 18, 2)->default(150);
            $table->decimal('paid_total', 18, 2)->default(50);
        });
        DB::table('companies')->insert(['id' => 1]);
        DB::table('suppliers')->insert(['id' => 1, 'name' => 'Supplier One']);
        $asOf = \Carbon\Carbon::parse('2026-10-28');
        foreach ([0, 30, 31, 60, 61, 90, 91] as $age) {
            DB::table('purchase_documents')->insert([
                'document_no' => 'BILL-'.$age, 'document_date' => $asOf->copy()->subDays($age)->toDateString(),
            ]);
        }
        foreach ([['currency' => 'USD'], ['status' => 'voided'], ['status' => 'cancelled'],
            ['paid_total' => 150], ['company_id' => 2], ['document_date' => '2026-10-29']] as $variant) {
            DB::table('purchase_documents')->insert(array_merge([
                'document_no' => 'VARIANT', 'document_date' => '2026-10-01',
            ], $variant));
        }
        request()->merge(['as_of' => '2026-10-28']);
        $data = app(ModuleController::class)->apAging()->getData();
        $this->assertCount(2, $data['companies']);
        $this->assertCount(8, $data['rows']);
        $php = $data['companies']->firstWhere('currency', 'PHP');
        $this->assertEquals([200, 200, 200, 100], $php['buckets']);
        $this->assertEquals(700, $php['balance']);
        $this->assertEquals(1050, $php['due']);
        $this->assertEquals(350, $php['paid']);
        $this->assertEquals(100, $data['currencyTotals']['USD']['balance']);
        request()->merge(['currency' => 'USD']);
        $filtered = app(ModuleController::class)->apAging()->getData();
        $this->assertCount(1, $filtered['rows']);
        $this->assertEquals('USD', $filtered['companies']->first()['currency']);

        request()->merge(['from' => '2026-07-01', 'to' => '2026-10-28', 'currency' => 'all', 'status' => 'all']);
        $summary = app(ModuleController::class)->expenseSummary()->getData();
        $this->assertContains('/erp/expenses/summary', route('erp.expenses.summary'));
        $this->assertCount(2, $summary['rows']);
        $this->assertEquals(1200, $summary['totals']['PHP']['Bill Amount']);
        $this->assertEquals(500, $summary['totals']['PHP']['Paid Amount']);
        $this->assertEquals(700, $summary['totals']['PHP']['Unpaid Balance']);
        $this->assertEquals(100, $summary['totals']['USD']['Unpaid Balance']);
        request()->merge(['status' => 'paid']);
        $paid = app(ModuleController::class)->expenseSummary()->getData();
        $this->assertCount(1, $paid['rows']);
        $this->assertEquals(150, $paid['totals']['PHP']['Paid Amount']);
        $this->assertEquals(0, $paid['totals']['PHP']['Unpaid Balance']);
        request()->merge(['status' => 'unpaid', 'currency' => 'PHP']);
        $unpaid = app(ModuleController::class)->expenseSummary()->getData();
        $this->assertEquals(7, $unpaid['totals']['PHP']['Total Bills']);
        $this->assertEquals(350, $unpaid['totals']['PHP']['Paid Amount']);
        $this->assertEquals(700, $unpaid['totals']['PHP']['Unpaid Balance']);

        DB::table('purchase_documents')->where('currency', 'USD')->update(['exchange_rate' => 56]);
        request()->merge(['currency' => 'USD', 'status' => 'all']);
        $converted = app(ModuleController::class)->expenseSummary()->getData();
        $this->assertEquals(8400, $converted['totals']['USD']['Bill Amount']);
        $this->assertEquals(2800, $converted['totals']['USD']['Paid Amount']);
        $this->assertEquals(5600, $converted['totals']['USD']['Unpaid Balance']);
        $aging = app(ModuleController::class)->apAging()->getData();
        $this->assertEquals(5600, $aging['currencyTotals']['USD']['balance']);
    }
}
