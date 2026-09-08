<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorNumberSeriesTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->actingAsAdmin();
    }

    public function test_vendor_id_uses_the_configurable_finance_number_series()
    {
        $companyId = DB::table('companies')->where('code', 'SJ')->value('id');
        $series = DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', 'vendor')
            ->first();

        $this->assertNotNull($series);
        $this->assertSame('VND-', $series->prefix);
        $this->assertSame(4, (int) $series->padding);

        $this->post(route('erp.settings.number-series.save'), [
            'id' => $series->id,
            'document_type' => 'vendor',
            'prefix' => 'VEN-',
            'current_number' => 41,
            'padding' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('erp.settings.index').'#number-series');

        $this->get(route('erp.suppliers.create'))
            ->assertOk()
            ->assertSee('value="VEN-00042"', false);

        $this->post(route('erp.suppliers.store'), [
            'supplier_id' => 'VEN-00042',
            'name' => 'Dynamic Vendor Number Test',
            'tax_classification' => '12% VAT',
            'payment_terms' => '30 Days',
            'currency' => 'PHP',
            'default_class_code' => 'MANILA',
        ])->assertRedirect(route('erp.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'company_id' => $companyId,
            'code' => 'VEN-00042',
        ]);
        $this->assertSame(42, (int) DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', 'vendor')
            ->value('current_number'));

        $this->get(route('erp.suppliers.create'))
            ->assertOk()
            ->assertSee('value="VEN-00043"', false);

        $this->post(route('erp.suppliers.store'), [
            'supplier_id' => '',
            'name' => 'Auto-generated Vendor Number Test',
            'tax_classification' => '12% VAT',
            'payment_terms' => '30 Days',
            'currency' => 'PHP',
            'default_class_code' => 'MANILA',
        ])->assertRedirect(route('erp.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'company_id' => $companyId,
            'code' => 'VEN-00043',
            'name' => 'Auto-generated Vendor Number Test',
        ]);
        $this->assertSame(43, (int) DB::table('number_series')
            ->where('company_id', $companyId)
            ->where('document_type', 'vendor')
            ->value('current_number'));

        $this->get(route('erp.suppliers.create'))
            ->assertOk()
            ->assertSee('value="VEN-00044"', false);
    }
}
