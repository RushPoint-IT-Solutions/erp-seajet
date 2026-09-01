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
            ->assertSee('Recently Posted Journal Entries');

        $accounts = DB::table('accounts')
            ->where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->all();

        $response = $this->post(route('erp.accounting.journals.review'), [
            'journal_no' => 'JV-MODULE-TEST',
            'journal_date' => '2026-08-30',
            'reference_no' => 'REF-MODULE-TEST',
            'shipment_job_id' => 'JOB-MODULE-TEST',
            'memo' => 'Journal module feature test',
            'line_account' => [$accounts[0], $accounts[1]],
            'line_description' => ['Debit line', 'Credit line'],
            'line_debit' => ['1250.00', ''],
            'line_credit' => ['', '1250.00'],
        ]);

        $journalId = DB::table('journal_entries')->where('journal_no', 'JV-MODULE-TEST')->value('id');
        $response->assertRedirect(route('erp.accounting.journals.show', $journalId));

        $this->get(route('erp.accounting.journals.show', $journalId))
            ->assertStatus(200)
            ->assertSee('JV-MODULE-TEST')
            ->assertSee('Journal module feature test')
            ->assertSee('1,250.00');

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalId,
            'debit' => 1250,
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalId,
            'credit' => 1250,
        ]);
    }
}
