<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionDataResetTest extends TestCase
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

    public function test_reset_removes_company_transactions_but_preserves_layouts_and_setup()
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        $layoutCount = DB::table('print_layouts')->count();
        $layoutSnapshot = DB::table('print_layouts')->orderBy('id')->get()->map(function ($layout) {
            return (array) $layout;
        })->all();
        $numberSeriesSnapshot = DB::table('number_series')->where('company_id', $companyId)->orderBy('id')->get()->map(function ($series) {
            return (array) $series;
        })->all();
        $customerCount = DB::table('customers')->where('company_id', $companyId)->count();
        $supplierCount = DB::table('suppliers')->where('company_id', $companyId)->count();
        $bankCount = DB::table('bank_accounts')->where('company_id', $companyId)->count();
        $sequencedBankId = DB::table('bank_accounts')->where('company_id', $companyId)->orderBy('id')->value('id');
        DB::table('bank_accounts')->where('id', $sequencedBankId)->update(['last_check_no' => '777899']);

        $this->assertGreaterThan(0, DB::table('sales_documents')->where('company_id', $companyId)->count());
        $this->assertGreaterThan(0, DB::table('purchase_documents')->where('company_id', $companyId)->count());
        $this->assertGreaterThan(0, DB::table('payments')->where('company_id', $companyId)->count());
        $this->assertGreaterThan(0, DB::table('journal_entries')->where('company_id', $companyId)->count());

        $this->post(route('erp.settings.transaction-data.reset'), [
            'confirmation' => 'RESET TRANSACTION DATA',
        ])->assertRedirect(route('erp.settings.index'));

        foreach (['sales_documents', 'purchase_documents', 'receipts', 'payments', 'expenses', 'journal_entries'] as $table) {
            $this->assertSame(0, DB::table($table)->where('company_id', $companyId)->count(), $table.' was not reset.');
        }
        foreach (['sales_document_lines', 'purchase_document_lines', 'vendor_bill_lines', 'receipt_applications', 'payment_applications', 'journal_entry_lines', 'check_issuances', 'check_print_events', 'check_voucher_print_events'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' was not reset.');
        }

        $this->assertSame($layoutCount, DB::table('print_layouts')->count());
        $this->assertEquals($layoutSnapshot, DB::table('print_layouts')->orderBy('id')->get()->map(function ($layout) {
            return (array) $layout;
        })->all());
        $this->assertEquals($numberSeriesSnapshot, DB::table('number_series')->where('company_id', $companyId)->orderBy('id')->get()->map(function ($series) {
            return (array) $series;
        })->all());
        $this->assertSame($customerCount, DB::table('customers')->where('company_id', $companyId)->count());
        $this->assertSame($supplierCount, DB::table('suppliers')->where('company_id', $companyId)->count());
        $this->assertSame($bankCount, DB::table('bank_accounts')->where('company_id', $companyId)->count());
        $this->assertSame('777899', DB::table('bank_accounts')->where('id', $sequencedBankId)->value('last_check_no'));
        $this->get(route('erp.payables.payments.create', ['method' => 'Cheque']))
            ->assertOk()
            ->assertSee('data-next-check-no="777900"', false);
        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $companyId,
            'action' => 'transaction_data_reset',
        ]);
    }

    public function test_reset_requires_the_exact_confirmation_value()
    {
        $before = DB::table('sales_documents')->count();

        $this->from(route('erp.settings.index'))->post(route('erp.settings.transaction-data.reset'), [
            'confirmation' => 'RESET',
        ])->assertRedirect(route('erp.settings.index'))->assertSessionHasErrors('confirmation');

        $this->assertSame($before, DB::table('sales_documents')->count());
    }

    public function test_reset_control_and_endpoint_are_restricted_to_administrators()
    {
        $user = new User();
        $user->id = 901;
        $user->name = 'QA Accounting';
        $user->email = 'accounting@seajet.test';
        $user->role = 'accounting';
        $user->company_id = DB::table('companies')->orderBy('id')->value('id');
        $user->is_active = true;
        $this->actingAs($user);

        $this->get(route('erp.settings.index'))
            ->assertOk()
            ->assertDontSee('Reset Data');

        $this->post(route('erp.settings.transaction-data.reset'), [
            'confirmation' => 'RESET TRANSACTION DATA',
        ])->assertStatus(403);
    }
}
