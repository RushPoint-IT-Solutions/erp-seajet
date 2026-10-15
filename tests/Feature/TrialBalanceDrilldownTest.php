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
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'journal_no' => 'JV-DRILL-001',
            'journal_date' => '2026-10-05',
            'source_module' => 'Sales Invoice',
            'reference_no' => 'BI-DRILL-001',
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
                'reference_no' => 'BI-DRILL-001',
                'description' => 'Raw debit line',
                'debit' => 125.5,
                'credit' => 0,
                'effect' => 125.5,
            ])
            ->assertJson(['total' => 125.5]);
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
