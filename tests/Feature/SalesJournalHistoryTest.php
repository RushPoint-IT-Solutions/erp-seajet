<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesJournalHistoryTest extends TestCase
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

    public function test_sales_splits_reimbursables_and_links_actual_invoice_and_soa_numbers()
    {
        $invoice = $this->invoice();
        $response = $this->get(route('erp.reports.workbook', ['report' => 'sales-manila', 'period' => '2026-10']));
        $response->assertOk()->assertSee('id="salesHistoryModal"', false)
            ->assertSee('data-history-url="'.route('erp.reports.sales-history', $invoice->id).'"', false)
            ->assertSee('>007263</a>', false)->assertDontSee('>7,263.00</a>', false);
        $row = collect($response->original->getData()['rows'])->firstWhere('BILLING INVOICE', $invoice->document_no);
        $this->assertNotNull($row);
        $this->assertSame('007263', $row['STATEMENT OF ACCOUNT NUMBER']);
        $this->assertEquals(112, $row['A/R TRADE']);
        $this->assertEquals(50, $row['A/R REIMBURSABLE CHARGES']);
        $this->assertEquals(50, $row['REIMBURSABLE CHARGES (CR)']);
        $this->assertEquals(100, $row['SERVICE INCOME VATABLE']);
        $this->assertEquals(12, $row['OUTPUT VAT']);
        $this->assertEquals(162, $row['A/R TRADE'] + $row['A/R REIMBURSABLE CHARGES']);
    }

    public function test_standalone_soa_and_branch_period_void_and_company_filters()
    {
        $invoice = $this->invoice();
        $copy = (array) $invoice;
        unset($copy['id']);
        $copy['document_no'] = 'SOAA-HISTORY';
        $copy['document_type'] = 'statement_of_account_a';
        $copy['soa_no'] = null;
        $copy['vat_classification'] = 'zero-rated';
        $copy['grand_total'] = 100;
        $copy['freight_total'] = 0;
        $copy['tax_total'] = 0;
        DB::table('sales_documents')->insert($copy);
        $foreignCompany = DB::table('companies')->insertGetId(['code' => 'OTHER', 'name' => 'Other Company', 'base_currency' => 'PHP']);
        foreach ([['status' => 'voided'], ['status' => 'cancelled'], ['class_code' => 'CEBU'],
            ['document_date' => '2026-09-01'], ['company_id' => $foreignCompany]] as $index => $changes) {
            DB::table('sales_documents')->insert(array_merge($copy, ['document_no' => 'EXCLUDED-'.$index], $changes));
        }
        $response = $this->get(route('erp.reports.workbook', ['report' => 'sales-manila', 'period' => '2026-10']))->assertOk();
        $rows = $response->original->getData()['rows'];
        $this->assertCount(2, $rows);
        $row = collect($rows)->firstWhere('STATEMENT OF ACCOUNT NUMBER', 'SOAA-HISTORY');
        $this->assertSame('', $row['BILLING INVOICE']);
        $this->assertEquals(100, $row['SERVICE INCOME ZERO RATED']);
        $this->assertEquals(0, $row['A/R REIMBURSABLE CHARGES']);
    }

    public function test_history_shows_payments_voids_and_escaped_document_changes()
    {
        $invoice = $this->invoice();
        foreach (['posted', 'voided'] as $status) {
            $receipt = DB::table('receipts')->insertGetId([
                'company_id' => $invoice->company_id, 'customer_id' => $invoice->customer_id,
                'receipt_no' => 'HISTORY-'.$status, 'receipt_date' => '2026-10-21',
                'payment_method' => 'Cash', 'amount' => 50, 'status' => $status, 'currency' => 'PHP',
            ]);
            DB::table('receipt_applications')->insert([
                'receipt_id' => $receipt, 'sales_document_id' => $invoice->id,
                'component' => 'soa', 'amount_applied' => 50,
            ]);
        }
        DB::table('sales_documents')->where('id', $invoice->id)->update(['paid_total' => 50]);
        DB::table('activity_logs')->insert([
            'company_id' => $invoice->company_id, 'auditable_type' => 'sales_document', 'auditable_id' => $invoice->id,
            'action' => 'status_changed', 'new_values' => json_encode(['status' => 'posted', 'reason' => '<script>alert(1)</script>']),
            'created_at' => '2026-10-21 09:00:00',
        ]);
        $this->get(route('erp.reports.sales-history', $invoice->id))->assertOk()
            ->assertSee('007263')->assertSee('HISTORY-posted')->assertSee('HISTORY-voided')->assertSee('Voided')
            ->assertSee('PHP 112.00')->assertSee('Status Changed')->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_history_cannot_open_another_company_document()
    {
        $invoice = $this->invoice();
        $company = DB::table('companies')->insertGetId(['code' => 'PRIVATE', 'name' => 'Private company', 'base_currency' => 'PHP']);
        DB::table('sales_documents')->where('id', $invoice->id)->update(['company_id' => $company]);
        $this->withExceptionHandling();
        $this->get(route('erp.reports.sales-history', $invoice->id))->assertNotFound();
    }

    private function invoice()
    {
        $invoice = DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'document_date' => '2026-10-21', 'soa_no' => '007263', 'class_code' => 'MANILA',
            'grand_total' => 162, 'freight_total' => 50, 'tax_total' => 12, 'status' => 'posted', 'paid_total' => 0,
        ]);
        return DB::table('sales_documents')->where('id', $invoice->id)->first();
    }
}
