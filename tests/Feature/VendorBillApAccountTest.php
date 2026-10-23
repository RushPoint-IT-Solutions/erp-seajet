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

    public function test_approved_legacy_php_bill_can_be_posted_and_repaired_to_ap_trade_php()
    {
        $supplier = DB::table('suppliers')->orderBy('id')->first();
        $expense = DB::table('accounts')->whereIn('type', ['Expense', 'Other Expense'])->orderBy('id')->first();
        DB::table('accounts')->where('id', $expense->id)->update(['show_in_ap' => true]);

        $this->post(route('erp.payables.vendor-bills.review'), $this->billPayload(
            $supplier,
            $expense,
            'Accounts Payable Trade',
            'close'
        ))->assertRedirect();

        $bill = DB::table('purchase_documents')->orderBy('id', 'desc')->first();
        DB::table('purchase_documents')->where('id', $bill->id)->update([
            'document_date' => '2026-09-14',
            'status' => 'approved',
            'approval_status' => 'Approved',
            'posting_status' => 'unposted',
            'journal_entry_id' => null,
        ]);

        $this->get(route('erp.expenses.index'))->assertOk()
            ->assertSee('Approved / Unposted')
            ->assertSee(route('erp.payables.vendor-bills.post', $bill->id), false);
        $this->post(route('erp.payables.vendor-bills.post', $bill->id))
            ->assertRedirect(route('erp.expenses.index'));

        $bill = DB::table('purchase_documents')->where('id', $bill->id)->first();
        $apAccount = DB::table('accounts')->where('name', 'A/P-Trade (BDO - PHP)')->first();
        $outputVat = DB::table('accounts')->where('name', 'like', 'Output V% Payable')->first();
        $payableLine = DB::table('journal_entry_lines')->where('journal_entry_id', $bill->journal_entry_id)
            ->where('description', 'Accounts payable - '.$bill->document_no)->first();
        $this->assertSame((int) $apAccount->id, (int) $payableLine->account_id);
        $this->assertEquals(1000, (float) $payableLine->credit);
        $this->assertSame((int) $apAccount->id, (int) DB::table('accounting_control_accounts')
            ->where('company_id', $bill->company_id)->where('control_key', 'accounts_payable')->value('account_id'));

        DB::table('journal_entry_lines')->where('id', $payableLine->id)->update(['account_id' => $outputVat->id]);
        $this->get(route('erp.expenses.index'))->assertOk()
            ->assertSee('Approved / Repair A/P')
            ->assertSee(route('erp.payables.vendor-bills.post', $bill->id), false);
        $this->post(route('erp.payables.vendor-bills.post', $bill->id))->assertRedirect(route('erp.expenses.index'));
        $this->assertSame((int) $apAccount->id, (int) DB::table('journal_entry_lines')->where('id', $payableLine->id)->value('account_id'));

        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $apAccount->id);
        $this->assertEquals(-1000, $row['BEGINNING BALANCE']);
    }

    public function test_bulk_action_posts_multiple_approved_php_bills_to_ap_trade_php()
    {
        $supplier = DB::table('suppliers')->orderBy('id')->first();
        $expense = DB::table('accounts')->whereIn('type', ['Expense', 'Other Expense'])->orderBy('id')->first();
        DB::table('accounts')->where('id', $expense->id)->update(['show_in_ap' => true]);
        DB::table('purchase_documents')->update([
            'status' => 'cancelled',
            'approval_status' => 'Cancelled',
        ]);

        $billIds = [];
        foreach (['2026-06-09', '2026-07-30'] as $date) {
            $payload = $this->billPayload($supplier, $expense, 'Accounts Payable Trade', 'close');
            $payload['bill_date'] = $date;
            $this->post(route('erp.payables.vendor-bills.review'), $payload)->assertRedirect();
            $billIds[] = DB::table('purchase_documents')->max('id');
        }

        $bills = DB::table('purchase_documents')->whereIn('id', $billIds)->orderBy('id')->get();
        $this->assertCount(2, $bills);
        DB::table('purchase_documents')->whereIn('id', $billIds)->update([
            'status' => 'approved',
            'approval_status' => 'Approved',
            'posting_status' => 'unposted',
            'journal_entry_id' => null,
        ]);

        $this->get(route('erp.expenses.index'))->assertOk()
            ->assertSee('Post/Repair Approved A/P')
            ->assertSee(route('erp.payables.vendor-bills.post-approved-php'), false);
        $this->post(route('erp.payables.vendor-bills.post-approved-php'))
            ->assertRedirect(route('erp.expenses.index'))
            ->assertSessionHas(
                'status',
                '2 approved PHP vendor bills posted/repaired to A/P Trade. 0 already correct or not eligible.'
            );

        $apAccountId = DB::table('accounts')->where('name', 'A/P-Trade (BDO - PHP)')->value('id');
        foreach (DB::table('purchase_documents')->whereIn('id', $billIds)->orderBy('id')->get() as $bill) {
            $this->assertSame('posted', $bill->posting_status);
            $this->assertNotNull($bill->journal_entry_id);
            $this->assertEquals(1000, (float) DB::table('journal_entry_lines')
                ->where('journal_entry_id', $bill->journal_entry_id)
                ->where('account_id', $apAccountId)
                ->value('credit'));
        }
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
