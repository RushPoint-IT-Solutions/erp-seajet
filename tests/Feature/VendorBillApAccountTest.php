<?php

namespace Tests\Feature;

use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorBillApAccountTest extends TestCase
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

    public function test_vendor_bill_lists_bdo_and_metrobank_ap_accounts_for_php_and_usd()
    {
        $this->get(route('erp.payables.vendor-bills.create'))
            ->assertOk()
            ->assertSee('A/P-Trade (BDO - PHP)')
            ->assertSee('A/P-Trade (BDO - USD)')
            ->assertSee('A/P-Trade (Metro-PHP)')
            ->assertSee('A/P-Trade (Metro-USD)')
            ->assertSee('BILL-B00001')
            ->assertSee('BILL-M00001');
    }

    public function test_bank_specific_bill_numbers_and_payable_posting_use_selected_ap_account()
    {
        $supplier = DB::table('suppliers')->orderBy('id')->first();
        $expense = DB::table('accounts')->whereIn('type', ['Expense', 'Other Expense'])->orderBy('id')->first();
        $this->assertNotNull($supplier);
        $this->assertNotNull($expense);
        DB::table('accounts')->where('id', $expense->id)->update(['show_in_ap' => true]);

        $bdoAccount = 'A/P-Trade (BDO - PHP)';
        $bdoResponse = $this->post(route('erp.payables.vendor-bills.review'), $this->billPayload(
            $supplier,
            $expense,
            $bdoAccount,
            'post'
        ));

        $bdoBill = DB::table('purchase_documents')->where('ap_account', $bdoAccount)->first();
        $bdoResponse->assertOk();
        $this->assertNotNull($bdoBill);
        $this->assertStringStartsWith('BILL-B', $bdoBill->document_no);

        $bdoAccountId = DB::table('accounts')->where('name', $bdoAccount)->value('id');
        $this->assertGreaterThan(0, (float) DB::table('journal_entry_lines')
            ->where('journal_entry_id', $bdoBill->journal_entry_id)
            ->where('account_id', $bdoAccountId)
            ->value('credit'));
        $this->assertEquals(0.0, (float) DB::table('journal_entry_lines')
            ->where('journal_entry_id', $bdoBill->journal_entry_id)
            ->where('account_id', $bdoAccountId)
            ->value('debit'));

        $bank = DB::table('bank_accounts')->where('company_id', $bdoBill->company_id)->where('currency', 'PHP')->first();
        $paymentId = DB::table('payments')->insertGetId([
            'company_id' => $bdoBill->company_id,
            'supplier_id' => $bdoBill->supplier_id,
            'bill_no' => $bdoBill->document_no,
            'payment_no' => 'PV-AP-BDO-TEST',
            'payment_date' => '2026-09-14',
            'payment_method' => 'Bank Transfer',
            'bank_account_id' => $bank->id,
            'bank_account' => $bank->account_no,
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'amount' => 1000,
            'status' => 'released',
            'approval_status' => 'Released',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentJournal = app(PostingService::class)->postSupplierPayment($paymentId);
        $this->assertEquals(1000.0, (float) DB::table('journal_entry_lines')
            ->where('journal_entry_id', $paymentJournal->id)
            ->where('account_id', $bdoAccountId)
            ->value('debit'));

        $bdoUsdAccount = 'A/P-Trade (BDO - USD)';
        $this->post(route('erp.payables.vendor-bills.review'), $this->billPayload(
            $supplier,
            $expense,
            $bdoUsdAccount,
            'close',
            'USD'
        ));
        $bdoUsdBill = DB::table('purchase_documents')->where('ap_account', $bdoUsdAccount)->first();
        $this->assertSame('BILL-B00002', $bdoUsdBill->document_no);

        $metroAccount = 'A/P-Trade (Metro-PHP)';
        $metroResponse = $this->post(route('erp.payables.vendor-bills.review'), $this->billPayload(
            $supplier,
            $expense,
            $metroAccount,
            'close'
        ));

        $metroBill = DB::table('purchase_documents')->where('ap_account', $metroAccount)->first();
        $metroResponse->assertRedirect(route('erp.payables.vendor-bills.view', $metroBill->document_no));
        $this->assertStringStartsWith('BILL-M', $metroBill->document_no);
        $this->assertNotSame($bdoBill->document_no, $metroBill->document_no);
    }

    private function billPayload($supplier, $expense, $apAccount, $saveMode, $currency = 'PHP')
    {
        return [
            'transaction_type' => 'Bill',
            'ap_account' => $apAccount,
            'supplier_id' => $supplier->code,
            'supplier_name' => $supplier->name,
            'bill_date' => '2026-09-14',
            'currency' => $currency,
            'exchange_rate' => $currency === 'USD' ? '58.25' : '1',
            'expense_account' => [$expense->code.' - '.$expense->name],
            'expense_tax' => [''],
            'expense_amount' => ['1,000.00'],
            'expense_memo' => ['Bank-specific AP test'],
            'expense_class' => ['MANILA'],
            'save_mode' => $saveMode,
        ];
    }
}
