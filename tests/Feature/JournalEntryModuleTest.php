<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalEntryModuleTest extends TestCase
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
        $this->withoutExceptionHandling();
    }

    public function test_journal_entry_module_is_visible_and_posts_balanced_entries()
    {
        $page = $this->get(route('erp.accounting.journals'));
        $page->assertStatus(200)
            ->assertSee('Create Journal Entry')
            ->assertSee('Journal Entries')
            ->assertSee('name="payee_supplier_id"', false)
            ->assertSee('name="payee"', false)
            ->assertSee('name="line_tax_category[]"', false)
            ->assertSee('name="line_withholding_tax_rate[]"', false)
            ->assertSee('Recently Posted Journal Entries');

        $accounts = DB::table('accounts')
            ->where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->all();
        $supplier = DB::table('suppliers')
            ->where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $response = $this->post(route('erp.accounting.journals.review'), [
            'journal_no' => 'JV-MODULE-TEST',
            'journal_date' => '2026-08-30',
            'reference_no' => 'REF-MODULE-TEST',
            'payee_supplier_id' => $supplier->id,
            'shipment_job_id' => 'JOB-MODULE-TEST',
            'memo' => 'Journal module feature test',
            'line_account' => [$accounts[0], $accounts[1]],
            'line_description' => ['Debit line', 'Credit line'],
            'line_tax_category' => ['VAT12', ''],
            'line_withholding_tax_rate' => ['2', '0'],
            'line_debit' => ['1250.00', ''],
            'line_credit' => ['', '1250.00'],
        ]);

        $journalId = DB::table('journal_entries')->where('journal_no', 'JV-MODULE-TEST')->value('id');
        $response->assertRedirect(route('erp.accounting.journals.show', $journalId));

        $this->get(route('erp.accounting.journals.show', $journalId))
            ->assertStatus(200)
            ->assertSee('JV-MODULE-TEST')
            ->assertSee($supplier->name)
            ->assertSee('Journal module feature test')
            ->assertSee('VAT 12%')
            ->assertSee('2% (25.00)')
            ->assertSee('1,250.00');

        $this->assertDatabaseHas('journal_entries', [
            'id' => $journalId,
            'payee' => $supplier->name,
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalId,
            'debit' => 1250,
            'supplier_id' => $supplier->id,
            'tax_category' => 'VAT12',
            'tax_amount' => 150,
            'withholding_tax_rate' => 2,
            'withholding_tax_amount' => 25,
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalId,
            'credit' => 1250,
        ]);

        $report = $this->get(route('erp.reports.workbook', [
            'report' => 'general-journal',
            'period' => '2026-08',
        ]));
        $report->assertStatus(200);
        $rows = collect($report->original->getData()['rows']);
        $this->assertTrue($rows->contains(function ($row) {
            return ($row['JV NO'] ?? '') === 'JV-MODULE-TEST'
                && !empty($row['PAYEE']);
        }));

        $this->get(route('erp.tax.vat-summary', ['period' => '2026-08']))
            ->assertStatus(200)
            ->assertSee('JV-MODULE-TEST')
            ->assertSee('150.00');

        $this->get(route('erp.tax.withholding'))
            ->assertStatus(200)
            ->assertSee('JV-MODULE-TEST')
            ->assertSee('Expanded Withholding Tax (JV)')
            ->assertSee('25.00');
    }
}
