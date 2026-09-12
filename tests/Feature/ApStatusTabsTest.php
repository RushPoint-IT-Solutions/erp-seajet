<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApStatusTabsTest extends TestCase
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

    public function test_ap_and_check_preparation_have_requested_status_tabs()
    {
        DB::table('purchase_documents')->where('document_no', 'BILL-000503')->update([
            'status' => 'voided',
            'approval_status' => 'Voided',
        ]);
        DB::table('payments')->where('payment_no', 'PV-000322')->update([
            'status' => 'voided',
            'approval_status' => 'Voided',
        ]);

        foreach ([route('erp.expenses.index'), route('erp.payables.approvals')] as $route) {
            $this->get($route)
                ->assertOk()
                ->assertSeeInOrder(['Approved', 'For Approval', 'Cancelled / Voided'])
                ->assertSee('data-status-group="approved"', false)
                ->assertSee('data-status-group="for_approval"', false)
                ->assertSee('data-status-group="cancelled_voided"', false);
        }
    }
}
