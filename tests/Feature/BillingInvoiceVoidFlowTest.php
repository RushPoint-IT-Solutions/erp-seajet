<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingInvoiceVoidFlowTest extends TestCase
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

    public function test_draft_invoice_is_cancelled_without_deleting_header_or_lines()
    {
        $invoice = $this->createInvoice('BI-CANCEL-001');
        $lineCount = DB::table('sales_document_lines')->where('sales_document_id', $invoice->id)->count();

        $response = $this->post(route('erp.billings.void.store', $invoice->id), [
            'reason' => 'Customer cancelled before posting.',
        ]);

        $response->assertRedirect(route('erp.invoices.show', $invoice->id));
        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'document_no' => 'BI-CANCEL-001',
            'status' => 'cancelled',
            'posting_status' => 'unposted',
        ]);
        $this->assertSame($lineCount, DB::table('sales_document_lines')->where('sales_document_id', $invoice->id)->count());
        $this->get(route('erp.billings.edit', $invoice->id))->assertStatus(403);
    }

    public function test_posted_invoice_is_voided_and_its_journal_is_reversed_without_deletion()
    {
        $invoice = $this->createInvoice('BI-VOID-001', 'post');
        $originalJournal = DB::table('journal_entries')
            ->where('source_type', 'sales_document')
            ->where('source_id', $invoice->id)
            ->where('posting_type', 'original')
            ->first();
        $this->assertNotNull($originalJournal);
        $lineCount = DB::table('sales_document_lines')->where('sales_document_id', $invoice->id)->count();

        $response = $this->post(route('erp.billings.void.store', $invoice->id), [
            'reason' => 'Incorrect customer billing was posted.',
        ]);

        $response->assertRedirect(route('erp.invoices.show', $invoice->id));
        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'document_no' => 'BI-VOID-001',
            'status' => 'voided',
            'posting_status' => 'reversed',
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'id' => $originalJournal->id,
            'status' => 'reversed',
        ]);
        $reversal = DB::table('journal_entries')
            ->where('source_type', 'sales_document')
            ->where('source_id', $invoice->id)
            ->where('posting_type', 'reversal')
            ->first();
        $this->assertNotNull($reversal);
        $this->assertEquals($originalJournal->id, $reversal->reversed_entry_id);
        $this->assertSame($lineCount, DB::table('sales_document_lines')->where('sales_document_id', $invoice->id)->count());
    }

    public function test_invoice_with_an_active_receipt_cannot_be_voided()
    {
        $invoice = $this->createInvoice('BI-PAID-VOID-001');
        DB::table('sales_documents')->where('id', $invoice->id)->update(['paid_total' => 25]);

        $response = $this->from(route('erp.billings.void', $invoice->id))
            ->post(route('erp.billings.void.store', $invoice->id), [
                'reason' => 'Attempt to void a paid invoice.',
            ]);

        $response->assertRedirect(route('erp.billings.void', $invoice->id));
        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'status' => 'draft',
        ]);
    }

    public function test_legacy_posted_invoice_without_a_journal_is_voided_instead_of_cancelled()
    {
        $invoice = $this->createInvoice('BI-LEGACY-VOID-001');
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'status' => 'posted',
            'posting_status' => 'unposted',
        ]);

        $page = $this->get(route('erp.billings.void', $invoice->id));
        $page->assertStatus(200);
        $page->assertSee('Void Posted Invoice');
        $page->assertSee('No posted journal exists for this legacy invoice');

        $response = $this->post(route('erp.billings.void.store', $invoice->id), [
            'reason' => 'Legacy posted invoice requires voiding.',
        ]);

        $response->assertRedirect(route('erp.invoices.show', $invoice->id));
        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'status' => 'voided',
            'posting_status' => 'unposted',
        ]);
    }

    private function createInvoice($documentNo, $saveMode = 'draft')
    {
        $response = $this->post('/erp/invoices/review', [
            'customer_id' => 'CUST-001',
            'customer_name' => 'North Harbor Trading',
            'job_shipment_id' => 'JOB-VOID-001',
            'invoice_no' => $documentNo,
            'transaction_no' => $documentNo,
            'soa_no' => 'SOA-VOID-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'invoice_to' => 'Test Customer',
            'consignee' => 'Test Consignee',
            'credit_term_days' => '30',
            'class_code' => 'MANILA',
            'currency' => 'PHP',
            'exchange_rate' => '1',
            'vat_classification' => '12%',
            'ar_account' => 'A/Trade-Billing Invoice',
            'invoice_template' => ['SOA_B', 'Billing Invoice'],
            'customer_tax_code' => 'VAT12',
            'withholding_tax_rate' => '0',
            'item' => ['Documentation VAT'],
            'item_description' => ['DOC / IDENTIFICATION FEE'],
            'qty' => ['1'],
            'unit_price' => ['100.00'],
            'line_class' => ['MANILA'],
            'line_ar_account' => ['A/Trade-Billing Invoice'],
            'tax_category' => ['12%'],
            'line_amount' => ['100.00'],
            'save_mode' => $saveMode,
        ]);

        $response->assertRedirect();

        return DB::table('sales_documents')->where('document_no', $documentNo)->first();
    }
}
