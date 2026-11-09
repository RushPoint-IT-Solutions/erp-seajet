<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceIssueRegressionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->actingAsAdmin();
    }

    public function test_actual_ap_status_overrides_stale_approval_labels()
    {
        $bill = DB::table('purchase_documents')->first();
        DB::table('purchase_documents')->where('id', $bill->id)->update(['status' => 'voided', 'approval_status' => 'Approved']);
        $data = $this->get(route('erp.expenses.index'))->assertOk()->original->getData();
        $row = collect($data['expenses'])->firstWhere('id', $bill->id);
        $this->assertSame('cancelled_voided', $row['status_group']);

        $payment = DB::table('payments')->first();
        DB::table('payments')->where('id', $payment->id)->update(['status' => 'approved', 'approval_status' => 'For Approval']);
        $rows = $this->get(route('erp.payables.approvals'))->assertOk()->original->getData()['approvals'];
        $row = collect($rows)->firstWhere('id', $payment->id);
        $this->assertSame('approved', $row['status_group']);
        $this->assertFalse($row['can_approve']);
    }

    public function test_aging_filters_accounts_and_exports_the_same_php_balances()
    {
        $account = DB::table('accounts')->where('type', 'Accounts Payable')->where('name', 'not like', '%USD%')->first();
        $bill = DB::table('purchase_documents')->where('currency', 'PHP')->first();
        DB::table('purchase_documents')->update(['status' => 'paid']);
        DB::table('purchase_documents')->where('id', $bill->id)->update([
            'status' => 'posted', 'ap_account' => $account->name, 'opening_balance_control_account_id' => $account->id,
            'grand_total' => 250, 'paid_total' => 50, 'document_date' => '2026-10-01',
        ]);
        $filters = ['as_of' => '2026-11-09', 'ap_account_id' => $account->id];
        $data = $this->get(route('erp.payables.aging', $filters))->assertOk()->assertSee('Export Excel')->original->getData();
        $this->assertCount(1, $data['rows']);
        $this->assertEquals(200, $data['rows']->first()['balance']);
        $export = $this->get(route('erp.payables.aging', array_merge($filters, ['format' => 'excel'])))->assertOk();
        $path = $export->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertContains('Balance (PHP)', $xml);
        $this->assertContains($bill->document_no, $xml);
        $this->assertContains('<v>200</v>', $xml);
        $zip->close();
        @unlink($path);
        $this->get(route('erp.payables.aging', ['ap_account_id' => 999999]))->assertStatus(422);
    }

    public function test_ar_edit_is_read_only_until_a_valid_replacement_is_saved()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'invoice_amount' => 0, 'freight_total' => 50, 'grand_total' => 50,
            'withholding_tax_amount' => 0, 'paid_total' => 0, 'balance_due' => 50, 'status' => 'posted',
        ]);
        $fields = [
            'customer_id' => (string) $invoice->customer_id, 'invoice_no' => [$invoice->document_no],
            'payment_for' => 'soa', 'class_code' => 'MANILA', 'payment_date' => '2026-10-08',
            'payment_method' => 'Cash', 'currency' => 'PHP', 'amount' => 50, 'receipt_no' => 'AR-EDIT-REGRESSION',
        ];
        $this->post(route('erp.payments.receipt'), $fields)->assertOk();
        $receipt = DB::table('receipts')->where('receipt_no', $fields['receipt_no'])->first();
        $paid = DB::table('sales_documents')->where('id', $invoice->id)->value('paid_total');
        $page = $this->get(route('erp.payments.create', ['edit_receipt' => $receipt->id]))->assertOk()->assertSee('Edit Payment');
        $option = collect($page->original->getData()['invoices'])->firstWhere('id', $invoice->id);
        $this->assertEquals(50, $option['soa_balance']);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => $receipt->status]);
        $fields['edit_receipt'] = $receipt->id;
        $fields['correction_reason'] = 'Correct the collection amount';
        $fields['amount'] = 999999999;
        $this->post(route('erp.payments.receipt'), $fields)->assertSessionHasErrors();
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => $receipt->status, 'posting_status' => 'posted']);
        $this->assertEquals($paid, DB::table('sales_documents')->where('id', $invoice->id)->value('paid_total'));
        $fields['amount'] = 25;
        $this->post(route('erp.payments.receipt'), $fields)->assertOk();
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'voided', 'posting_status' => 'reversed']);
        $replacement = DB::table('receipts')->where('receipt_no', $fields['receipt_no'])->where('status', '<>', 'voided')->first();
        $this->assertEquals(25, $replacement->amount);
        $this->assertDatabaseHas('receipt_applications', ['receipt_id' => $replacement->id, 'sales_document_id' => $invoice->id, 'amount_applied' => 25]);
    }
}
