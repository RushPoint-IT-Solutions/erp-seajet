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
            'created_by' => auth()->id(),
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
            ->assertSee('Encoded By')
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
                'encoded_by' => DB::table('users')->where('id', auth()->id())->value('name'),
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

    public function test_trial_balance_excludes_opening_and_payment_lines_when_billing_invoice_is_voided()
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('opening_balance', 0)->orderBy('id')->first();
        $balancingAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->orderBy('id')->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'status' => 'voided',
            'posting_status' => 'reversed',
            'paid_total' => 0,
        ]);

        $journals = [
            ['OB-INACTIVE-BILLING', '2026-09-01', 'GL', 'opening_balance_import', 'A/R Trade opening balance '.$invoice->document_no, 250, 0],
            ['OR-INACTIVE-BILLING', '2026-09-15', 'AR', 'receipt', 'Applied to '.$invoice->document_no, 0, 250],
            ['OR-INACTIVE-REVERSAL', '2026-10-05', 'AR', 'receipt', 'Reversal: Applied to '.$invoice->document_no, 250, 0],
            ['OB-INACTIVE-REVERSAL', '2026-10-05', 'GL', 'opening_balance_row', 'Reversal: A/R Trade opening balance '.$invoice->document_no, 0, 250],
        ];

        foreach ($journals as $index => $journal) {
            $journalId = DB::table('journal_entries')->insertGetId([
                'company_id' => 1,
                'journal_no' => $journal[0],
                'journal_date' => $journal[1],
                'source_module' => $journal[2],
                'source_type' => $journal[3],
                'source_id' => $invoice->id + $index,
                'reference_no' => $invoice->document_no,
                'memo' => $journal[4],
                'status' => 'posted',
            ]);
            DB::table('journal_entry_lines')->insert([
                ['journal_entry_id' => $journalId, 'account_id' => $account->id, 'description' => $journal[4], 'debit' => $journal[5], 'credit' => $journal[6]],
                ['journal_entry_id' => $journalId, 'account_id' => $balancingAccount->id, 'description' => $journal[4], 'debit' => $journal[6], 'credit' => $journal[5]],
            ]);
        }

        $response = $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10',
            'account_id' => $account->id,
            'column' => 'ENDING TRIAL BALANCE',
        ]));

        $response->assertStatus(200)
            ->assertJson(['entries' => [], 'total' => 0]);

        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(0, (float) $row['BEGINNING BALANCE']);
        $this->assertEquals(0, (float) $row['JOURNAL BOOK']);
        $this->assertEquals(0, (float) $row['ENDING TRIAL BALANCE']);
    }

    public function test_trial_balance_excludes_orphaned_direct_invoice_journals()
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('opening_balance', 0)->orderBy('id')->first();
        $balancingAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->orderBy('id')->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'journal_no' => 'JV-ORPHANED-BILLING',
            'journal_date' => '2026-10-08',
            'source_module' => 'Sales Invoice',
            'source_type' => 'sales_document',
            'source_id' => $invoice->id,
            'reference_no' => $invoice->document_no,
            'memo' => 'Orphaned billing journal',
            'status' => 'posted',
        ]);
        DB::table('journal_entry_lines')->insert([
            ['journal_entry_id' => $journalId, 'account_id' => $account->id, 'description' => 'Orphaned invoice debit', 'debit' => 99, 'credit' => 0],
            ['journal_entry_id' => $journalId, 'account_id' => $balancingAccount->id, 'description' => 'Orphaned invoice credit', 'debit' => 0, 'credit' => 99],
        ]);

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('sales_documents')->where('id', $invoice->id)->delete();
        DB::statement('PRAGMA foreign_keys = ON');

        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10',
            'account_id' => $account->id,
            'column' => 'SALES BOOK',
        ]))->assertStatus(200)->assertJson([
            'entries' => [],
            'total' => 0,
        ]);
    }

    public function test_trial_balance_keeps_legacy_opening_line_when_active_invoice_matches_document_number()
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('opening_balance', 0)->orderBy('id')->first();
        $balancingAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->orderBy('id')->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update([
            'status' => 'draft',
            'posting_status' => 'posted',
        ]);

        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1,
            'journal_no' => 'OB-LEGACY-ACTIVE-BILLING',
            'journal_date' => '2026-09-01',
            'source_module' => 'GL',
            'source_type' => 'opening_balance_import',
            'source_id' => 1,
            'reference_no' => $invoice->document_no,
            'memo' => 'Legacy A/R opening balance '.$invoice->document_no,
            'status' => 'posted',
        ]);
        DB::table('journal_entry_lines')->insert([
            ['journal_entry_id' => $journalId, 'account_id' => $account->id, 'description' => 'Legacy A/R opening balance '.$invoice->document_no, 'debit' => 175, 'credit' => 0],
            ['journal_entry_id' => $journalId, 'account_id' => $balancingAccount->id, 'description' => 'Legacy A/R opening balance '.$invoice->document_no, 'debit' => 0, 'credit' => 175],
        ]);

        $fileHash = str_repeat('a', 64);
        $batchId = DB::table('opening_balance_import_batches')->insertGetId([
            'company_id' => 1,
            'original_filename' => 'legacy-active-invoice.xlsx',
            'file_hash' => $fileHash,
            'posting_date' => '2026-09-01',
            'journal_entry_id' => $journalId,
        ]);
        DB::table('opening_balance_import_rows')->insert([
            'batch_id' => $batchId,
            'company_id' => 1,
            'file_hash' => $fileHash,
            'sheet_name' => 'AR',
            'row_number' => 2,
            'balance_type' => 'receivable',
            'branch_name' => 'Main',
            'party_name' => 'Legacy Customer',
            'source_date' => '2026-09-01',
            'reference_no' => $invoice->document_no,
            'subledger_document_no' => $invoice->document_no,
            'sales_document_id' => null,
            'currency' => 'PHP',
            'exchange_rate' => 1,
            'base_amount' => 175,
            'journal_entry_id' => $journalId,
        ]);

        $response = $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10',
            'account_id' => $account->id,
            'column' => 'BEGINNING BALANCE',
        ]));

        $response->assertStatus(200)
            ->assertJsonFragment(['journal_no' => 'OB-LEGACY-ACTIVE-BILLING'])
            ->assertJson(['total' => 175]);

        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(175, (float) $row['BEGINNING BALANCE']);
    }
}
