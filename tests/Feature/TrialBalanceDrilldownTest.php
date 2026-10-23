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
        $this->seed(\SeaJetTestDataSeeder::class);
        $this->seed(\BillingReimbursementSampleSeeder::class);
        $this->actingAsAdmin();
    }

    public function test_payable_import_is_not_excluded_as_a_missing_sales_invoice()
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('code', '2000')->first();
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1, 'journal_no' => 'OB-AP-REGRESSION',
            'journal_date' => '2026-08-31', 'source_module' => 'GL',
            'source_type' => 'opening_balance_import', 'status' => 'posted',
        ]);
        DB::table('journal_entry_lines')->insert([
            'journal_entry_id' => $journalId, 'account_id' => $account->id,
            'description' => 'A/P Trade opening balance AP-REGRESSION | Vendor | 2026-08-31',
            'debit' => 0, 'credit' => 500,
        ]);
        $hash = str_repeat('b', 64);
        $batchId = DB::table('opening_balance_import_batches')->insertGetId([
            'company_id' => 1, 'original_filename' => 'ap.xlsx',
            'file_hash' => $hash, 'posting_date' => '2026-08-31', 'journal_entry_id' => $journalId,
        ]);
        DB::table('opening_balance_import_rows')->insert([
            'batch_id' => $batchId, 'company_id' => 1, 'file_hash' => $hash,
            'sheet_name' => 'AP', 'row_number' => 2, 'balance_type' => 'ap_trade',
            'branch_name' => 'Main', 'party_name' => 'Vendor', 'source_date' => '2026-08-31',
            'reference_no' => 'AP-REGRESSION', 'subledger_document_no' => 'AP-REGRESSION',
            'sales_document_id' => null, 'currency' => 'PHP', 'exchange_rate' => 1,
            'base_amount' => 500, 'journal_entry_id' => $journalId,
        ]);
        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-09']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(-500, $row['BEGINNING BALANCE']);
        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-09', 'account_id' => $account->id, 'column' => 'BEGINNING BALANCE',
        ]))->assertOk()->assertJsonFragment(['journal_no' => 'OB-AP-REGRESSION']);
    }

    public function test_prior_period_vendor_bill_is_not_hidden_by_a_voided_sales_document_with_the_same_reference()
    {
        $apAccount = DB::table('accounts')->where('company_id', 1)->where('code', '2000')->first();
        $offsetAccount = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $apAccount->id)->first();
        $bill = DB::table('purchase_documents')->where('company_id', 1)->first();
        $invoice = (array) DB::table('sales_documents')->where('company_id', 1)->first();
        unset($invoice['id']);
        DB::table('sales_documents')->insert(array_merge($invoice, [
            'document_no' => '21', 'transaction_no' => '21', 'soa_no' => '21', 'status' => 'voided',
        ]));

        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1, 'journal_no' => 'AP-BEG-21', 'journal_date' => '2026-08-20',
            'source_module' => 'AP', 'source_type' => 'purchase_document', 'source_id' => $bill->id,
            'reference_no' => '21', 'status' => 'posted', 'posting_type' => 'original',
        ]);
        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $journalId, 'account_id' => $offsetAccount->id,
                'description' => 'Opening balance equity - 21', 'debit' => 11446.23, 'credit' => 0,
            ],
            [
                'journal_entry_id' => $journalId, 'account_id' => $apAccount->id,
                'description' => 'Accounts payable - 21', 'debit' => 0, 'credit' => 11446.23,
            ],
        ]);

        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-09']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $apAccount->id);
        $this->assertEquals(-11446.23, $row['BEGINNING BALANCE']);
        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-09', 'account_id' => $apAccount->id, 'column' => 'BEGINNING BALANCE',
        ]))->assertOk()->assertJsonFragment(['journal_no' => 'AP-BEG-21']);
    }

    public function test_voided_journals_and_their_reversals_are_hidden_from_all_trial_balance_columns()
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('opening_balance', 0)->first();
        $receipt = DB::table('receipts')->where('company_id', 1)->first();
        $this->assertNotNull($receipt);
        DB::table('receipts')->where('id', $receipt->id)->update(['status' => 'voided']);
        $payment = DB::table('payments')->where('company_id', 1)->first();
        $bill = DB::table('purchase_documents')->where('company_id', 1)->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($bill);
        DB::table('payments')->where('id', $payment->id)->update(['status' => 'voided']);
        DB::table('purchase_documents')->where('id', $bill->id)->update(['status' => 'voided']);
        $originalId = null;
        foreach ([
            ['voided', 'original', null, null, null, '2026-09-05', 100],
            ['Voided', 'original', null, null, null, '2026-10-05', 200],
            ['reversed', 'original', null, null, null, '2026-09-05', 300],
            ['posted', 'reversal', null, null, 'previous', '2026-10-05', -300],
            ['posted', 'original', 'receipt', $receipt->id, null, '2026-10-05', 400],
            ['posted', 'original', 'payment', $payment->id, null, '2026-10-05', 500],
            ['posted', 'original', 'purchase_document', $bill->id, null, '2026-10-05', 600],
            ['posted', 'original', null, null, null, '2026-10-05', 25],
        ] as $index => $entry) {
            list($status, $postingType, $sourceType, $sourceId, $reversedId, $date, $amount) = $entry;
            $id = DB::table('journal_entries')->insertGetId([
                'company_id' => 1, 'journal_no' => 'JV-VOID-FILTER-'.$index,
                'journal_date' => $date, 'source_module' => 'GL', 'status' => $status,
                'posting_type' => $postingType, 'source_type' => $sourceType,
                'source_id' => $sourceId, 'reversed_entry_id' => $reversedId ? $originalId : null,
            ]);
            if ($status === 'reversed') {
                $originalId = $id;
            }
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $id, 'account_id' => $account->id,
                'description' => 'Void filtering fixture',
                'debit' => max(0, $amount), 'credit' => max(0, -$amount),
            ]);
        }

        foreach (['BEGINNING BALANCE' => 0, 'JOURNAL BOOK' => 25, 'ENDING TRIAL BALANCE' => 25] as $column => $total) {
            $response = $this->getJson(route('erp.reports.trial-balance.drilldown', [
                'period' => '2026-10', 'account_id' => $account->id, 'column' => $column,
            ]))->assertOk()->assertJson(['total' => $total]);
            $this->assertCount($total ? 1 : 0, $response->json('entries'));
        }
        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(0, $row['BEGINNING BALANCE']);
        $this->assertEquals(25, $row['JOURNAL BOOK']);
        $this->assertEquals(25, $row['ENDING TRIAL BALANCE']);
    }

    public function test_voiding_one_imported_balance_keeps_the_other_encoded_balances_in_the_batch()
    {
        $account = DB::table('accounts')->where('company_id', 1)->where('opening_balance', 0)->first();
        $offset = DB::table('accounts')->where('company_id', 1)->where('id', '<>', $account->id)->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update(['status' => 'voided']);
        $original = DB::table('journal_entries')->insertGetId([
            'company_id' => 1, 'journal_no' => 'OB-PARTIAL-VOID', 'journal_date' => '2026-09-01',
            'source_module' => 'GL', 'source_type' => 'opening_balance_import', 'status' => 'posted',
        ]);
        $reversal = DB::table('journal_entries')->insertGetId([
            'company_id' => 1, 'journal_no' => 'OB-ROW-REVERSAL', 'journal_date' => '2026-10-05',
            'source_module' => 'GL', 'source_type' => 'opening_balance_row', 'status' => 'posted',
            'posting_type' => 'reversal', 'reversed_entry_id' => $original,
        ]);
        foreach ([
            [$original, 'A/R Trade opening balance '.$invoice->document_no, 100],
            [$original, 'A/R Trade opening balance ACTIVE-ENCODED-2026', 175],
            [$reversal, 'Reversal: A/R Trade opening balance '.$invoice->document_no, -100],
        ] as $entry) {
            DB::table('journal_entry_lines')->insert([
                ['journal_entry_id' => $entry[0], 'account_id' => $account->id,
                    'description' => $entry[1], 'debit' => max(0, $entry[2]), 'credit' => max(0, -$entry[2])],
                ['journal_entry_id' => $entry[0], 'account_id' => $offset->id,
                    'description' => $entry[1], 'credit' => max(0, $entry[2]), 'debit' => max(0, -$entry[2])],
            ]);
        }
        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10', 'account_id' => $account->id, 'column' => 'BEGINNING BALANCE',
        ]))->assertOk()->assertJson(['total' => 175])->assertJsonFragment([
            'description' => 'A/R Trade opening balance ACTIVE-ENCODED-2026',
        ])->assertJsonCount(1, 'entries');
        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(175, $row['BEGINNING BALANCE']);
        $this->assertEquals(175, $row['ENDING TRIAL BALANCE']);
    }

    public function test_blank_voided_document_numbers_do_not_hide_encoded_journals()
    {
        $invoice = (array) DB::table('sales_documents')->where('company_id', 1)->first();
        unset($invoice['id']);
        DB::table('sales_documents')->insert(array_merge($invoice, [
            'document_no' => '', 'transaction_no' => '', 'soa_no' => '', 'status' => 'voided',
        ]));
        $account = DB::table('accounts')->where('company_id', 1)->where('opening_balance', 0)->first();
        $journalId = DB::table('journal_entries')->insertGetId([
            'company_id' => 1, 'journal_no' => 'JV-ENCODED-NO-REFERENCE',
            'journal_date' => '2026-10-05', 'source_module' => 'GL',
            'reference_no' => '', 'status' => 'posted',
        ]);
        DB::table('journal_entry_lines')->insert([
            'journal_entry_id' => $journalId, 'account_id' => $account->id,
            'description' => 'Manually encoded active entry', 'debit' => 250, 'credit' => 0,
        ]);
        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10', 'account_id' => $account->id, 'column' => 'JOURNAL BOOK',
        ]))->assertOk()->assertJson(['total' => 250])
            ->assertJsonFragment(['journal_no' => 'JV-ENCODED-NO-REFERENCE']);
        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(250, $row['JOURNAL BOOK']);
    }

    public function test_expense_beginning_balance_keeps_stored_and_manual_journal_amounts()
    {
        $accountId = DB::table('accounts')->insertGetId([
            'company_id' => 1, 'code' => '5999-QA', 'name' => 'Expense beginning balance test',
            'type' => 'Expenses', 'opening_balance' => 100,
            'account_category_id' => DB::table('account_categories')->where('name', 'Expenses')->value('id'),
        ]);
        $account = DB::table('accounts')->where('id', $accountId)->first();
        $invoice = DB::table('sales_documents')->where('company_id', 1)->first();
        DB::table('sales_documents')->where('id', $invoice->id)->update(['status' => 'voided']);
        foreach ([['posted', 250, 0], ['posted', 0, 25], ['voided', 500, 0]] as $index => $entry) {
            $journalId = DB::table('journal_entries')->insertGetId([
                'company_id' => 1, 'journal_no' => 'JV-EXPENSE-OPENING-'.$index,
                'journal_date' => '2026-09-01', 'source_module' => 'GL',
                'reference_no' => $invoice->document_no, 'status' => $entry[0],
            ]);
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalId, 'account_id' => $account->id,
                'description' => 'Expense opening adjustment '.$invoice->document_no,
                'debit' => $entry[1], 'credit' => $entry[2],
            ]);
        }
        $this->getJson(route('erp.reports.trial-balance.drilldown', [
            'period' => '2026-10', 'account_id' => $account->id, 'column' => 'BEGINNING BALANCE',
        ]))->assertOk()->assertJson(['total' => 325])->assertJsonCount(3, 'entries');
        $rows = $this->get(route('erp.reports.trial-balance', ['period' => '2026-10']))
            ->assertOk()->original->getData()['rows'];
        $row = collect($rows)->firstWhere('__account_id', (int) $account->id);
        $this->assertEquals(325, $row['BEGINNING BALANCE']);
        $this->assertEquals(325, $row['ENDING TRIAL BALANCE']);
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
}
