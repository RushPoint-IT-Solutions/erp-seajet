<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorBill2307PrintTest extends TestCase
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

    public function test_vendor_bill_inherits_wc_details_and_prints_bir_2307()
    {
        $account = DB::table('accounts')
            ->where('atc_code', 'WC160')
            ->where('is_active', true)
            ->first();
        $supplier = DB::table('suppliers')->whereNotNull('tin')->orderBy('id')->first();

        $this->assertNotNull($account);
        $this->assertNotNull($supplier);

        $accountLabel = $account->code.' - '.$account->name;
        $response = $this->post(route('erp.payables.vendor-bills.review'), [
            'transaction_type' => 'Bill',
            'supplier_id' => $supplier->code,
            'supplier_name' => $supplier->name,
            'supplier_address' => $supplier->address,
            'bill_date' => '2026-08-28',
            'currency' => 'PHP',
            'exchange_rate' => '1',
            'expense_account' => [$accountLabel],
            'expense_tax' => ['VAT12'],
            'expense_amount' => ['11,200.00'],
            'expense_memo' => ['Test service expense'],
            'expense_customer_job' => [''],
            'expense_class' => ['MANILA'],
            'save_mode' => 'close',
        ]);

        $bill = DB::table('purchase_documents')->orderBy('id', 'desc')->first();
        $line = DB::table('vendor_bill_lines')->where('purchase_document_id', $bill->id)->first();

        $response->assertRedirect(route('erp.payables.vendor-bills.view', $bill->document_no));
        $this->assertSame('WC160', $line->atc_code);
        $this->assertEquals(2.0, (float) $line->withholding_tax_rate);
        $this->assertEquals(200.0, (float) $line->withholding_tax_amount);
        $this->assertEquals(200.0, (float) $bill->withholding_tax_amount);
        $this->assertEquals(11000.0, (float) $bill->grand_total);

        $this->get(route('erp.payables.vendor-bills.print-2307', $bill->id))
            ->assertOk()
            ->assertSee('images/bir_2307_bg.png')
            ->assertSee('WC160')
            ->assertSee('10,000.00')
            ->assertSee('200.00')
            ->assertSee($supplier->name);
    }
}
