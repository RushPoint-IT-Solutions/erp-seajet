<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrialBalanceDrilldownTest extends TestCase
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

    public function test_trial_balance_amount_opens_a_raw_journal_line_drilldown()
    {
        $account = DB::table('accounts')->where('company_id', 1)->orderBy('id')->first();
        $balancingAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->orderBy('id')->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update(['status' => 'draft', 'paid_total' => 0]);
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'journal_no' => 'JV-DRILL-001',
            'journal_date' => '2026-10-05',
            'source_module' => 'Sales Invoice',
            'source_type' => 'sales_document',
            'source_id' => $invoice->id,
            'reference_no' => $invoice->document_no,
            'memo' => 'Trial balance drilldown test',
            'status' => 'posted',
        ]);

        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $journalId,
                'account_id' => $account->id,
                'description' => 'Raw debit line',
                'debit' => 125.50,
                'credit' => 0,
            ],
            [
                'journal_entry_id' => $journalId,
                'account_id' => $balancingAccount->id,
                'description' => 'Raw credit line',
                'debit' => 0,
                'credit' => 125.50,
            ],
        ]);

        $page = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']));
        $page->assertStatus(200)
            ->assertSee('data-trial-drilldown', false)
            ->assertSee('var endpoint =', false);

        $response = $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10',
            'account_id' => $account->id,
            'column' => 'SALES BOOK',
        ]));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'journal_no' => 'JV-DRILL-001',
                'reference_no' => $invoice->document_no,
                'description' => 'Raw debit line',
                'debit' => 125.5,
                'credit' => 0,
                'effect' => 125.5,
            ])
            ->assertJsonFragment([
                'source_label' => 'View Billing',
                'source_url' => route('erp.invoices.show', $invoice->id),
                'journal_url' => route('erp.accounting.journals.show', $journalId),
            ])
            ->assertJson(['total' => 125.5]);
    }

    public function test_vendor_bill_raw_item_links_to_the_bill_and_journal()
    {
        $account = DB::table('accounts')->where('company_id', 1)->orderBy('id')->first();
        $balancingAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->orderBy('id')->first();
        $bill = DB::table('purchase_documents')->where('company_id', 1)->first();
        $this->assertNotNull($bill);
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'journal_no' => 'JV-BILL-DRILL-001',
            'journal_date' => '2026-10-07',
            'source_module' => 'GL',
            'source_type' => 'purchase_document',
            'source_id' => $bill->id,
            'reference_no' => $bill->document_no,
            'memo' => 'Vendor bill drilldown test',
            'status' => 'posted',
        ]);
        DB::table('journal_entry_lines')->insert([
            ['journal_entry_id' => $journalId, 'account_id' => $account->id, 'description' => 'Vendor bill raw debit', 'debit' => 88, 'credit' => 0],
            ['journal_entry_id' => $journalId, 'account_id' => $balancingAccount->id, 'description' => 'Vendor bill raw credit', 'debit' => 0, 'credit' => 88],
        ]);

        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10',
            'account_id' => $account->id,
            'column' => 'JOURNAL BOOK',
        ]))->assertStatus(200)->assertJsonFragment([
            'journal_no' => 'JV-BILL-DRILL-001',
            'source_label' => 'View Vendor Bill',
            'source_url' => route('erp.payables.vendor-bills.view', $bill->id),
            'journal_url' => route('erp.accounting.journals.show', $journalId),
        ]);
    }

    public function test_trial_balance_and_drilldown_exclude_deleted_invoice_journals_with_legacy_source_names()
    {
        $account = DB::table('accounts')->where('company_id', 1)->orderBy('id')->first();
        $balancingAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->orderBy('id')->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update(['status' => 'voided']);

        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'journal_no' => 'JV-DELETED-INVOICE',
            'journal_date' => '2026-10-06',
            'source_module' => 'Sales Invoice',
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'reference_no' => $invoice->document_no,
            'memo' => 'Deleted invoice journal must not appear',
            'status' => 'posted',
        ]);

        DB::table('journal_entry_lines')->insert([
            ['journal_entry_id' => $journalId, 'account_id' => $account->id, 'description' => 'Deleted invoice debit', 'debit' => 321, 'credit' => 0],
            ['journal_entry_id' => $journalId, 'account_id' => $balancingAccount->id, 'description' => 'Deleted invoice credit', 'debit' => 0, 'credit' => 321],
        ]);

        $response = $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10',
            'account_id' => $account->id,
            'column' => 'SALES BOOK',
        ]));

        $response->assertStatus(200)
            ->assertJsonMissing(['journal_no' => 'JV-DELETED-INVOICE'])
            ->assertJson(['entries' => [], 'total' => 0]);

        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(0, (float) $row['SALES BOOK']);
    }
}
