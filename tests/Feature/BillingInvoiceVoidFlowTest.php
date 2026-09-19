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
        $this->assertFinalInvoiceIsAuditOnly($invoice);
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
        $this->assertFinalInvoiceIsAuditOnly($invoice);
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

    public function test_ar_aging_report_is_a_tab_inside_the_invoice_list()
    {
        $invoice = $this->createInvoice('BI-AGING-TAB-001');

        $page = $this->get(route('erp.invoices.index', [
            'tab' => 'aging',
            'as_of' => now()->toDateString(),
            'currency' => 'PHP',
        ]));

        $page->assertStatus(200)
            ->assertSee('Active')
            ->assertSee('Voided / Cancelled')
            ->assertSee('Aging Report')
            ->assertSee('As of Date')
            ->assertSee('BI-AGING-TAB-001')
            ->assertSee('Current');
        $this->assertSame('aging', $page->original->getData()['selectedInvoiceTab']);
        $this->assertTrue(collect($page->original->getData()['agingReportRows'])->contains(function ($row) use ($invoice) {
            return ($row[3] ?? '') === $invoice->document_no;
        }));
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

    private function assertFinalInvoiceIsAuditOnly($invoice)
    {
        $documentTypes = ['invoice', 'billing_invoice', 'statement_of_account_a', 'soa_a', 'debit_note'];
        $activeInvoices = DB::table('sales_documents')
            ->where('company_id', $invoice->company_id)
            ->whereIn('document_type', $documentTypes)
            ->whereNotIn('status', ['cancelled', 'voided']);

        $invoicePage = $this->get(route('erp.invoices.index'));
        $invoicePage->assertStatus(200);
        $invoicePage->assertSee('Voided / Cancelled');
        $this->assertFalse(collect($invoicePage->original->getData()['invoices'])->contains(function ($row) use ($invoice) {
            return ($row[0] ?? '') === $invoice->document_no;
        }));
        $stats = collect($invoicePage->original->getData()['stats'])->keyBy('label');
        $this->assertSame((int) (clone $activeInvoices)->count(), $stats['Invoices']['value']);
        $this->assertSame(
            'PHP '.number_format((float) (clone $activeInvoices)->sum('grand_total'), 2),
            $stats['Total Billed']['value']
        );
        $finalInvoiceCount = DB::table('sales_documents')
            ->where('company_id', $invoice->company_id)
            ->whereIn('document_type', $documentTypes)
            ->whereIn('status', ['cancelled', 'voided'])
            ->count();
        $this->assertSame($finalInvoiceCount, $invoicePage->original->getData()['invoiceTabCounts']['voided']);

        $voidedPage = $this->get(route('erp.invoices.index', ['tab' => 'voided']));
        $voidedPage->assertStatus(200);
        $this->assertTrue(collect($voidedPage->original->getData()['invoices'])->contains(function ($row) use ($invoice) {
            return ($row[0] ?? '') === $invoice->document_no;
        }));
        $this->assertSame('voided', $voidedPage->original->getData()['selectedInvoiceTab']);

        $customerPage = $this->get(route('erp.customers.show', $invoice->customer_id));
        $customerPage->assertStatus(200);
        $customerPage->assertSee($invoice->document_no);
        $summary = $customerPage->original->getData()['summary'];
        $customerInvoices = DB::table('sales_documents')
            ->where('company_id', $invoice->company_id)
            ->where('customer_id', $invoice->customer_id)
            ->whereIn('document_type', $documentTypes)
            ->whereNotIn('status', ['cancelled', 'voided']);
        $this->assertSame((int) (clone $customerInvoices)->count(), $summary['invoice_count']);
        $this->assertEquals((float) (clone $customerInvoices)->sum('grand_total'), $summary['billed']);
        $this->assertEquals((float) (clone $customerInvoices)->sum('paid_total'), $summary['paid']);

        $workbookPage = $this->get(route('erp.reports.workbook', ['report' => 'sales-manila']));
        $workbookPage->assertStatus(200);
        $workbookData = $workbookPage->original->getData();
        $this->assertFalse(collect($workbookData['rows'])->contains(function ($row) use ($invoice) {
            return ($row['BILLING INVOICE'] ?? '') === $invoice->document_no
                || ($row['STATEMENT OF ACCOUNT NUMBER'] ?? '') === $invoice->document_no;
        }));
        $salesCard = collect($workbookData['summaryCards'])->firstWhere('label', 'Sales Book');
        $expectedSales = DB::table('sales_documents')
            ->whereNotIn('status', ['cancelled', 'voided'])
            ->sum('grand_total');
        $this->assertSame('PHP '.number_format((float) $expectedSales, 2), $salesCard['value']);

        $journalNumbers = DB::table('journal_entries')
            ->where('source_type', 'sales_document')
            ->where('source_id', $invoice->id)
            ->pluck('journal_no');
        foreach (['general-ledger', 'general-journal'] as $report) {
            $reportPage = $this->get(route('erp.reports.workbook', [
                'report' => $report,
                'period' => date('Y-m'),
            ]));
            $reportPage->assertStatus(200);
            $reportRows = collect($reportPage->original->getData()['rows']);
            foreach ($journalNumbers as $journalNumber) {
                $this->assertFalse($reportRows->contains(function ($row) use ($journalNumber) {
                    return ($row['JV NO'] ?? '') === $journalNumber
                        || strpos((string) ($row['SOURCE'] ?? ''), (string) $journalNumber) !== false;
                }));
            }
        }

        foreach (['erp.customer-billing.aging', 'erp.tax.vat-summary', 'erp.tax.sales-purchases'] as $reportRoute) {
            $reportPage = $this->get(route($reportRoute));
            $reportPage->assertStatus(200);
            $reportPage->assertDontSee($invoice->document_no);
        }

        $reportsIndex = $this->get(route('erp.reports.index'));
        $reportsIndex->assertStatus(200);
        $openArCard = collect($reportsIndex->original->getData()['reportStats'])->firstWhere('label', 'Open AR');
        $expectedOpenAr = DB::table('sales_documents')
            ->where('company_id', $invoice->company_id)
            ->whereNotIn('status', ['cancelled', 'voided', 'paid'])
            ->selectRaw('COALESCE(SUM(grand_total - paid_total), 0) as balance')
            ->value('balance');
        $this->assertSame('PHP '.number_format((float) $expectedOpenAr, 2), $openArCard['value']);

        $dashboardPage = $this->get(route('erp.dashboard'));
        $dashboardPage->assertStatus(200);
        $revenueKpi = collect($dashboardPage->original->getData()['kpis'])->firstWhere('label', 'Revenue');
        $activeCompanySales = DB::table('sales_documents')
            ->where('company_id', $invoice->company_id)
            ->whereNotIn('status', ['cancelled', 'voided']);
        $this->assertEquals((float) (clone $activeCompanySales)->sum('grand_total'), $revenueKpi['value']);
        $this->assertSame((clone $activeCompanySales)->count().' sales docs', $revenueKpi['trend']);
    }
}
