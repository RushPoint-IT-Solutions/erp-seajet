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
        $assetAccount = DB::table('accounts')
            ->join('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
            ->where('account_categories.name', 'Assets')
            ->where('accounts.is_active', true)
            ->select('accounts.*')
            ->first();
        $supplier = DB::table('suppliers')->whereNotNull('tin')->orderBy('id')->first();
        $customer = DB::table('customers')->orderBy('id')->first();

        $this->assertNotNull($account);
        $this->assertNotNull($assetAccount);
        $this->assertNotNull($supplier);
        $this->assertNotNull($customer);
        $this->get(route('erp.payables.vendor-bills.create'))
            ->assertOk()
            ->assertSee($assetAccount->code.' - '.$assetAccount->name)
            ->assertSee('Bill Memo')
            ->assertDontSee('Item Memo');

        $accountLabel = $account->code.' - '.$account->name;
        $response = $this->post(route('erp.payables.vendor-bills.review'), [
            'transaction_type' => 'Bill',
            'supplier_id' => $supplier->code,
            'supplier_name' => $supplier->name,
            'supplier_address' => $supplier->address,
            'bill_date' => '2026-08-28',
            'currency' => 'PHP',
            'exchange_rate' => '1',
            'memo' => 'Header bill memo',
            'expense_account' => [$accountLabel],
            'expense_tax' => ['VAT12'],
            'expense_amount' => ['10,000.00'],
            'expense_memo' => ['Test service expense'],
            'expense_customer_job' => [$customer->customer_id],
            'expense_class' => ['MANILA'],
            'save_mode' => 'close',
        ]);

        $bill = DB::table('purchase_documents')->orderBy('id', 'desc')->first();
        $line = DB::table('vendor_bill_lines')->where('purchase_document_id', $bill->id)->first();

        $response->assertRedirect(route('erp.payables.vendor-bills.view', $bill->document_no));
        $this->assertSame('WC160', $line->atc_code);
        $this->assertEquals(2.0, (float) $line->withholding_tax_rate);
        $this->assertEquals(200.0, (float) $line->withholding_tax_amount);
        $this->assertEquals(10000.0, (float) $bill->subtotal);
        $this->assertEquals(1200.0, (float) $bill->tax_total);
        $this->assertEquals(11200.0, (float) $bill->gross_amount);
        $this->assertEquals(200.0, (float) $bill->withholding_tax_amount);
        $this->assertEquals(11000.0, (float) $bill->grand_total);
        $this->assertSame('Header bill memo', $bill->memo);
        $this->assertSame('Test service expense', $line->memo);

        $vendorBillPrint = $this->get(route('erp.payables.vendor-bills.print', $bill->id));
        $vendorBillPrint->assertOk()
            ->assertSee('width: 320px', false)
            ->assertSee('margin: 12px 0 0 28px', false)
            ->assertSee('white-space: nowrap', false)
            ->assertSee('width: 50%', false)
            ->assertSee('margin: 0 0 0 auto', false)
            ->assertSee('grid-template-columns: 44% 56%', false)
            ->assertDontSee('class="filler"', false)
            ->assertSee('Header bill memo')
            ->assertDontSee('Test service expense')
            ->assertSee($account->name)
            ->assertDontSee($accountLabel)
            ->assertSee(e($customer->name), false)
            ->assertDontSee(e($customer->customer_id.' - '.$customer->name), false)
            ->assertSee('Expanded Withholding Tax Payable')
            ->assertSee('10,000.00')
            ->assertSee('-200.00')
            ->assertSee('Expense Total : 9,800.00')
            ->assertSee('VAT 12% : 1,200.00')
            ->assertSee('Bill Total :PHP 11,000.00')
            ->assertDontSee('>Tax</th>', false)
            ->assertDontSee('>Memo</th>', false)
            ->assertDontSee('ATC/WC');

        $this->get(route('erp.payables.payments.create', ['bill' => $bill->document_no, 'method' => 'Cheque']))
            ->assertOk()
            ->assertSee('id="amountInput" class="form-control" value="11000.00"', false);

        $this->post(route('erp.settings.bir-2307.payee.save'), [
            'supplier_id' => $supplier->id,
            'bir_2307_payee_representative_name' => 'Sample Payee Signatory',
            'bir_2307_payee_representative_title' => 'Authorized Representative',
            'bir_2307_payee_representative_tin' => '111-222-333-000',
        ])->assertRedirect();

        $printResponse = $this->get(route('erp.payables.vendor-bills.print-2307', $bill->id));
        $printResponse->assertOk()
            ->assertSee('images/bir_2307_bg.png')
            ->assertSee('WC160')
            ->assertSee('Income payments made by top withholding agents to local/resident suppliers of services')
            ->assertDontSee('Export Air Expense')
            ->assertSee('10,000.00')
            ->assertSee('200.00')
            ->assertSee($supplier->name)
            ->assertSee('Lock positions')
            ->assertSee('Save Layout')
            ->assertSee('data-field-key="payee_name"', false)
            ->assertSee('data-field-key="period_from_char_0"', false)
            ->assertSee('data-field-key="period_to_char_0"', false)
            ->assertSee('data-field-key="payee_tin_char_0"', false)
            ->assertSee('data-field-key="payee_address_char_0"', false)
            ->assertSee('data-field-key="payer_tin_char_0"', false)
            ->assertSee('data-field-key="payer_tin_char_16"', false)
            ->assertSee('data-field-key="payer_address_char_0"', false)
            ->assertDontSee('data-field-key="payee_tin"', false)
            ->assertDontSee('data-field-key="payee_address"', false)
            ->assertDontSee('data-field-key="payer_tin"', false)
            ->assertDontSee('data-field-key="payer_address"', false)
            ->assertSee('data-field-key="detail_0_atc"', false)
            ->assertSee('data-field-key="payer_authorized_name"', false)
            ->assertSee('data-field-key="payee_authorized_name"', false)
            ->assertSee('Gina C. Boloy')
            ->assertSee('Finance Manager')
            ->assertSee('TIN: 293-263-579-000')
            ->assertSee('Sample Payee Signatory')
            ->assertSee('TIN: 111-222-333-000');

        preg_match('/var layout = (\{.*\});/', $printResponse->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);
        $layout = json_decode($matches[1], true);
        $this->assertIsArray($layout);
        $layout['payee_name'] = ['top' => 220, 'left' => 60, 'width' => 700, 'font_size' => 13];
        $layout['payee_tin_char_0'] = ['top' => 183, 'left' => 281, 'width' => 18, 'font_size' => 12];

        $this->postJson(route('erp.payables.vendor-bills.print-2307.layout.save'), ['layout' => [
            'payee_name' => $layout['payee_name'],
            'payee_tin_char_0' => $layout['payee_tin_char_0'],
        ]])
            ->assertOk()
            ->assertJsonFragment(['status' => 'BIR 2307 print layout saved.'])
            ->assertJsonFragment(['top' => 220, 'left' => 60, 'width' => 700, 'font_size' => 13]);

        $saved = DB::table('print_layouts')
            ->where('company_id', $bill->company_id)
            ->where('document_type', 'bir_2307')
            ->first();
        $this->assertNotNull($saved);

        $this->get(route('erp.payables.vendor-bills.print-2307', $bill->id))
            ->assertOk()
            ->assertSee('top:220px;left:60px;width:700px;font-size:13px', false)
            ->assertSee('top:183px;left:281px;width:18px;font-size:12px', false);
    }

    public function test_sample_2307_vendor_bill_seeder_is_idempotent_and_printable()
    {
        Artisan::call('db:seed', ['--class' => 'VendorBill2307SampleSeeder']);
        Artisan::call('db:seed', ['--class' => 'VendorBill2307SampleSeeder']);

        $bills = DB::table('purchase_documents')->where('document_no', 'BILL-2307-SAMPLE')->get();
        $this->assertCount(1, $bills);
        $bill = $bills->first();
        $this->assertEquals(11200.0, (float) $bill->gross_amount);
        $this->assertEquals(200.0, (float) $bill->withholding_tax_amount);
        $this->assertEquals(11000.0, (float) $bill->grand_total);

        $line = DB::table('vendor_bill_lines')->where('purchase_document_id', $bill->id)->first();
        $this->assertNotNull($line);
        $this->assertSame('WC160', $line->atc_code);
        $this->assertEquals(2.0, (float) $line->withholding_tax_rate);

        $this->get(route('erp.payables.vendor-bills.print-2307', $bill->id))
            ->assertOk()
            ->assertSee('WC160')
            ->assertSee('10,000.00')
            ->assertSee('200.00')
            ->assertSee('UTS LOGISTICS AND DISTRIBUTION CO., INC.');
    }

    public function test_payor_tin_is_editable_in_company_profile_and_used_by_2307()
    {
        $company = DB::table('companies')->where('code', 'SJ')->first();

        $this->post(route('erp.settings.company.save'), [
            'name' => $company->name,
            'tin' => '123-456-789-54321',
            'address' => $company->address,
            'base_currency' => $company->base_currency,
            'bir_2307_payor_representative_name' => $company->bir_2307_payor_representative_name,
            'bir_2307_payor_representative_title' => $company->bir_2307_payor_representative_title,
            'bir_2307_payor_representative_tin' => $company->bir_2307_payor_representative_tin,
        ])->assertRedirect(route('erp.settings.index'));

        $this->assertSame('123-456-789-54321', DB::table('companies')->where('id', $company->id)->value('tin'));

        Artisan::call('db:seed', ['--class' => 'VendorBill2307SampleSeeder']);
        $billId = DB::table('purchase_documents')->where('document_no', 'BILL-2307-SAMPLE')->value('id');
        $response = $this->get(route('erp.payables.vendor-bills.print-2307', $billId));
        $response->assertOk();

        preg_match_all('/data-field-key="payer_tin_char_(\d+)"[^>]*>(.*?)<\/div>/', $response->getContent(), $matches, PREG_SET_ORDER);
        $printedTin = '';
        foreach ($matches as $match) {
            $printedTin .= html_entity_decode(strip_tags($match[2]), ENT_QUOTES, 'UTF-8');
        }

        $this->assertSame('123-456-789-54321', $printedTin);
    }

    public function test_2307_prints_only_lines_with_withholding()
    {
        $withheldAccount = DB::table('accounts')->where('atc_code', 'WC160')->where('is_active', true)->first();
        $assetAccount = DB::table('accounts')
            ->join('account_categories', 'account_categories.id', '=', 'accounts.account_category_id')
            ->where('account_categories.name', 'Assets')
            ->where('accounts.is_active', true)
            ->select('accounts.*')
            ->first();
        $supplier = DB::table('suppliers')->whereNotNull('tin')->orderBy('id')->first();

        $this->post(route('erp.payables.vendor-bills.review'), [
            'transaction_type' => 'Bill',
            'supplier_id' => $supplier->code,
            'supplier_name' => $supplier->name,
            'supplier_address' => $supplier->address,
            'bill_date' => '2026-08-28',
            'currency' => 'PHP',
            'exchange_rate' => '1',
            'expense_account' => [
                $withheldAccount->code.' - '.$withheldAccount->name,
                $assetAccount->code.' - '.$assetAccount->name,
            ],
            'expense_tax' => ['VAT12', 'EXEMPT'],
            'expense_atc_code' => ['WC160', ''],
            'expense_withholding_rate' => ['2', '0'],
            'expense_amount' => ['10,000.00', '500.00'],
            'expense_memo' => ['Withheld service', 'Asset purchase without withholding'],
            'expense_customer_job' => ['', ''],
            'expense_class' => ['MANILA', 'MANILA'],
            'save_mode' => 'close',
        ])->assertRedirect();

        $billId = DB::table('purchase_documents')->orderBy('id', 'desc')->value('id');
        $this->get(route('erp.payables.vendor-bills.print-2307', $billId))
            ->assertOk()
            ->assertSee('WC160')
            ->assertSee('Income payments made by top withholding agents to local/resident suppliers of services')
            ->assertDontSee($assetAccount->name)
            ->assertDontSee('Asset purchase without withholding');

        DB::table('vendor_bill_lines')->where('purchase_document_id', $billId)->update([
            'withholding_tax_rate' => 0,
            'withholding_tax_amount' => 0,
        ]);
        DB::table('purchase_documents')->where('id', $billId)->update([
            'withholding_tax_rate' => 0,
            'withholding_tax_amount' => 0,
        ]);

        $this->get(route('erp.payables.vendor-bills.print-2307', $billId))
            ->assertOk()
            ->assertDontSee('WC160')
            ->assertDontSee('Income payments made by top withholding agents to local/resident suppliers of services')
            ->assertDontSee($assetAccount->name);
    }

    public function test_2307_uses_the_equivalent_income_payment_description_for_each_configured_atc()
    {
        $descriptions = [
            'WC100' => 'Rentals of real or personal property used in business',
            'WC158' => 'Income payments made by top withholding agents to local/resident suppliers of goods',
            'WC160' => 'Income payments made by top withholding agents to local/resident suppliers of services',
            'WI011' => 'Professional fees for services rendered by individuals',
            'WI139' => 'Brokerage fees paid to individual brokers',
        ];
        $rates = ['WC100' => 5, 'WC158' => 1, 'WC160' => 2, 'WI011' => 10, 'WI139' => 2];
        $supplier = DB::table('suppliers')->whereNotNull('tin')->orderBy('id')->first();
        $accounts = [];

        foreach (array_keys($descriptions) as $code) {
            $account = DB::table('accounts')->where('atc_code', $code)->orderBy('id')->first();
            $this->assertNotNull($account, 'Missing seeded account for '.$code);
            $accounts[] = trim(($account->code ? $account->code.' - ' : '').$account->name);
        }

        $this->post(route('erp.payables.vendor-bills.review'), [
            'transaction_type' => 'Bill',
            'supplier_id' => $supplier->code,
            'supplier_name' => $supplier->name,
            'supplier_address' => $supplier->address,
            'bill_date' => '2026-08-28',
            'currency' => 'PHP',
            'exchange_rate' => '1',
            'expense_account' => $accounts,
            'expense_tax' => array_fill(0, count($accounts), 'EXEMPT'),
            'expense_atc_code' => array_keys($descriptions),
            'expense_withholding_rate' => array_values($rates),
            'expense_amount' => array_fill(0, count($accounts), '1,000.00'),
            'expense_memo' => array_fill(0, count($accounts), ''),
            'expense_customer_job' => array_fill(0, count($accounts), ''),
            'expense_class' => array_fill(0, count($accounts), 'MANILA'),
            'save_mode' => 'close',
        ])->assertRedirect();

        $billId = DB::table('purchase_documents')->orderBy('id', 'desc')->value('id');
        $response = $this->get(route('erp.payables.vendor-bills.print-2307', $billId));
        $response->assertOk();

        foreach ($descriptions as $code => $description) {
            $response->assertSee($code)->assertSee($description);
        }
    }
}
