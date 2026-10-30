<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArAgingCompanyReportTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->seed(\BillingReimbursementSampleSeeder::class);
        $this->actingAsAdmin();
        $this->withoutExceptionHandling();
    }

    public function test_groups_invoices_by_company_and_currency_with_invoice_date_buckets()
    {
        $original = (array) DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        unset($original['id']);
        DB::table('sales_documents')->update(['status' => 'voided']);
        $asOf = \Carbon\Carbon::parse('2026-10-28');
        foreach ([0, 30, 31, 60, 61, 90, 91] as $age) {
            $invoice = array_merge($original, [
                'document_no' => 'AGING-TEST-'.$age,
                'document_type' => 'billing_invoice',
                'document_date' => $asOf->copy()->subDays($age)->toDateString(),
                'due_date' => '2026-12-31',
                'currency' => 'PHP', 'status' => 'posted', 'class_code' => 'CEBU',
                'grand_total' => 150, 'paid_total' => 50, 'balance_due' => 100,
                'invoice_amount' => 150, 'freight_total' => 0, 'withholding_tax_amount' => 0,
            ]);
            DB::table('sales_documents')->insert($invoice);
        }
        DB::table('sales_documents')->insert(array_merge($invoice, [
            'document_no' => 'AGING-USD', 'currency' => 'USD',
        ]));
        DB::table('sales_documents')->insert(array_merge($invoice, [
            'document_no' => 'AGING-FUTURE', 'document_date' => '2026-10-29',
        ]));
        $this->get(route('erp.customer-billing.aging', ['as_of' => '2026-10-28']))
            ->assertOk()->assertSee('Summary by Company')->assertSee('Detail by Company / Invoice')
            ->assertSee('Print Summary')->assertSee('Print Detail')->assertSee('Print All')
            ->assertDontSee('AGING-FUTURE')->assertSee('AGING-TEST-91')
            ->assertViewHas('companies', function ($companies) {
                $php = $companies->firstWhere('currency', 'PHP');
                return $companies->count() === 2 && $php['balance'] == 700
                    && $php['due'] == 1050 && $php['paid'] == 350
                    && $php['buckets'] === [200.0, 200.0, 200.0, 100.0];
            })
            ->assertViewHas('currencyTotals', function ($totals) {
                return $totals['PHP']['balance'] == 700 && $totals['USD']['balance'] == 100;
            });
        $this->get(route('erp.customer-billing.aging', ['as_of' => '2026-10-28', 'class' => 'MANILA']))
            ->assertOk()->assertSee('No unpaid active invoices found');
    }

    public function test_aging_deducts_withholding_from_invoice_amount_and_outstanding_balance()
    {
        $original = (array) DB::table('sales_documents')->where('document_no', 'BI-2026-REIMB')->first();
        unset($original['id']);
        DB::table('sales_documents')->update(['status' => 'voided']);
        foreach ([0, 50, 140] as $paid) {
            DB::table('sales_documents')->insert(array_merge($original, [
                'document_no' => 'AGING-WHT-'.$paid, 'document_type' => 'billing_invoice',
                'document_date' => '2026-10-01', 'due_date' => '2026-10-31',
                'currency' => 'PHP', 'status' => 'posted', 'grand_total' => 150,
                'invoice_amount' => 150, 'freight_total' => 0, 'withholding_tax_amount' => 10,
                'paid_total' => $paid, 'balance_due' => 150 - $paid,
            ]));
        }
        $this->get(route('erp.customer-billing.aging', ['as_of' => '2026-10-28']))
            ->assertOk()->assertDontSee('AGING-WHT-140')
            ->assertViewHas('companies', function ($companies) {
                $company = $companies->first();
                return $company['due'] == 280 && $company['paid'] == 50
                    && $company['balance'] == 230 && $company['buckets'] === [230.0, 0, 0, 0];
            });
    }
}
