<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorEditTest extends TestCase
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

    public function test_vendor_can_be_opened_and_updated_from_masterlist()
    {
        $vendor = DB::table('suppliers')->orderBy('id')->first();
        $accountId = DB::table('accounts')->where('company_id', $vendor->company_id)->value('id');

        $this->get(route('erp.suppliers.index'))
            ->assertOk()
            ->assertSee(route('erp.suppliers.edit', $vendor->id))
            ->assertSee('title="Edit Vendor"', false);

        $this->get(route('erp.suppliers.edit', $vendor->id))
            ->assertOk()
            ->assertSee('Edit Vendor')
            ->assertSee('name="zip_code"', false)
            ->assertSee('value="'.$vendor->code.'"', false)
            ->assertSee('value="'.$vendor->name.'"', false)
            ->assertSee('name="_method" value="PUT"', false);

        $this->put(route('erp.suppliers.update', $vendor->id), [
            'supplier_id' => $vendor->code,
            'name' => 'Updated Vendor Name',
            'tin' => '123-456-789-000',
            'tax_classification' => '12% VAT',
            'payment_terms' => '60 Days',
            'currency' => 'PHP',
            'default_class_code' => 'MANILA',
            'contact_person' => 'Updated Contact',
            'email' => 'updated.vendor@example.com',
            'phone' => '+63 917 555 0101',
            'address' => 'Updated Vendor Address',
            'zip_code' => '1704',
            'default_ap_account' => 'Accounts Payable Trade',
            'default_expense_account_id' => $accountId,
            'preferred_payment_method' => 'Cheque',
            'withholding_tax' => '2% EWT',
        ])->assertRedirect(route('erp.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $vendor->id,
            'company_id' => $vendor->company_id,
            'name' => 'Updated Vendor Name',
            'payment_terms' => '60 Days',
            'payment_term_days' => 60,
            'withholding_tax' => '2% EWT',
            'zip_code' => '1704',
        ]);
    }
}
