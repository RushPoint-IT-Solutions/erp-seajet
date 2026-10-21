<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentPaidIndicatorTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->actingAsAdmin();
        $this->withoutExceptionHandling();
    }

    public function test_invoice_and_soa_b_show_paid_only_for_the_settled_component()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update(['invoice_amount' => 112, 'paid_total' => 0]);
        $this->preview('erp.invoice.preview', $invoice)->assertDontSee('aria-label="Fully paid"', false);
        $this->receipt($invoice, 'bi', 112, '2026-10-20');
        DB::table('sales_documents')->where('id', $invoice->id)->update(['paid_total' => 112]);
        $this->preview('erp.invoice.preview', $invoice)->assertSee('aria-label="Fully paid"', false)->assertSee('10/20/2026');
        $this->preview('erp.soa.preview', $invoice)->assertDontSee('aria-label="Fully paid"', false);
        $soaReceipt = $this->receipt($invoice, 'soa', 50, '2026-10-21');
        DB::table('sales_documents')->where('id', $invoice->id)->update(['paid_total' => 162]);
        $this->preview('erp.soa.preview', $invoice)->assertSee('aria-label="Fully paid"', false)->assertSee('10/21/2026');
        $this->get(route('erp.invoices.show', $invoice->id))->assertOk()->assertSee('aria-label="Fully paid"', false);
        $this->preview('erp.invoice.preview', $invoice)->assertSee('10/20/2026');
        DB::table('receipts')->where('id', $soaReceipt)->update(['status' => 'voided']);
        DB::table('sales_documents')->where('id', $invoice->id)->update(['paid_total' => 112]);
        $this->preview('erp.soa.preview', $invoice)->assertDontSee('aria-label="Fully paid"', false);
        $this->preview('erp.invoice.preview', $invoice)->assertSee('aria-label="Fully paid"', false);
    }

    public function test_soa_a_requires_full_settlement_and_cancelled_documents_have_no_paid_badge()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'document_type' => 'statement_of_account_a', 'grand_total' => 162, 'invoice_amount' => 112, 'paid_total' => 100,
        ]);
        $this->receipt($invoice, 'soa', 100, '2026-10-20');
        $this->preview('erp.soa-a.preview', $invoice)->assertDontSee('aria-label="Fully paid"', false);
        $this->receipt($invoice, 'soa', 62, '2026-10-21');
        DB::table('sales_documents')->where('id', $invoice->id)->update(['paid_total' => 162]);
        $this->preview('erp.soa-a.preview', $invoice)->assertSee('aria-label="Fully paid"', false)->assertSee('10/21/2026');
        DB::table('sales_documents')->where('id', $invoice->id)->update(['status' => 'cancelled']);
        $this->preview('erp.soa-a.preview', $invoice)->assertDontSee('aria-label="Fully paid"', false);
    }

    public function test_bill_badge_follows_settlement_and_disappears_when_payment_is_reversed()
    {
        $bill = DB::table('purchase_documents')->orderBy('id')->first();
        DB::table('purchase_documents')->where('id', $bill->id)->update(['paid_total' => 0, 'status' => 'for_payment']);
        $this->get(route('erp.payables.vendor-bills.view', $bill->document_no))->assertOk()->assertDontSee('aria-label="Fully paid"', false);
        DB::table('purchase_documents')->where('id', $bill->id)->update(['paid_total' => $bill->grand_total, 'status' => 'paid']);
        DB::table('payments')->where('bill_no', $bill->document_no)->update(['approval_status' => 'Released', 'status' => 'released', 'payment_date' => '2026-10-21']);
        $this->get(route('erp.payables.vendor-bills.view', $bill->document_no))->assertOk()
            ->assertSee('aria-label="Fully paid"', false)->assertSee('10/21/2026');
        $this->get(route('erp.payables.vendor-bills.print', $bill->document_no))->assertOk()->assertSee('aria-label="Fully paid"', false);
        DB::table('purchase_documents')->where('id', $bill->id)->update(['paid_total' => 1, 'status' => 'partially_paid']);
        $this->get(route('erp.payables.vendor-bills.view', $bill->document_no))->assertOk()->assertDontSee('aria-label="Fully paid"', false);
    }

    private function preview($route, $invoice)
    {
        return $this->get(route($route, ['invoice' => $invoice->document_no]))->assertOk();
    }

    private function receipt($invoice, $component, $amount, $date)
    {
        $id = DB::table('receipts')->insertGetId([
            'company_id' => $invoice->company_id, 'customer_id' => $invoice->customer_id,
            'receipt_no' => 'PAID-'.uniqid(), 'receipt_date' => $date, 'payment_method' => 'Cash',
            'amount' => $amount, 'status' => 'posted',
        ]);
        DB::table('receipt_applications')->insert([
            'receipt_id' => $id, 'sales_document_id' => $invoice->id, 'component' => $component, 'amount_applied' => $amount,
        ]);
        return $id;
    }
}
