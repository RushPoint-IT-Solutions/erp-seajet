<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeaJetProductionSeedTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed', ['--class' => 'SeaJetProductionDataSeeder']);
    }

    public function test_it_seeds_the_excel_masterlists_without_demo_transactions()
    {
        $this->assertSame(1, DB::table('companies')->where('code', 'SJ')->count());
        $this->assertSame(3, DB::table('branches')->count());
        $this->assertSame(125, DB::table('accounts')->count());
        $this->assertSame(102, DB::table('customers')->count());
        $this->assertSame(135, DB::table('suppliers')->count());
        $this->assertSame(219, DB::table('items')->count());

        $this->assertSame(0, DB::table('sales_documents')->count());
        $this->assertSame(0, DB::table('receipts')->count());
        $this->assertSame(0, DB::table('purchase_documents')->count());
        $this->assertSame(0, DB::table('payments')->count());
    }

    public function test_it_preserves_source_fields_that_the_old_importer_discarded()
    {
        $item = DB::table('items')->where('sku', 'ITM-0001')->first();
        $this->assertSame('A.freight RE', $item->name);
        $this->assertSame('AIRFREIGHT CHARGES (95% OF FREIGHT)', $item->description);
        $this->assertSame('Non', $item->tax_type);
        $this->assertSame('Reimbursable charges', $item->account_name);

        $vendor = DB::table('suppliers')->where('name', 'ALDRIN A. AGUILA')->first();
        $this->assertSame('VAT-exempt', $vendor->tax_classification);

        $company = DB::table('companies')->where('code', 'SJ')->first();
        $this->assertSame('000-143-896-00000', $company->tin);
    }
}
