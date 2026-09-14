<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApItemAccountVisibilityTest extends TestCase
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

    public function test_any_active_account_type_can_be_enabled_for_ap_items()
    {
        $account = DB::table('accounts')
            ->where('type', 'Income')
            ->first();

        $this->assertNotNull($account);

        $editPage = $this->get(route('erp.accounting.coa.edit', $account->id));
        $editPage->assertOk()
            ->assertSee('Show in AP Item Account dropdown')
            ->assertSee('Any active account may be enabled.')
            ->assertDontSee('apCheckbox.disabled', false);

        $response = $this->put(route('erp.accounting.coa.update', $account->id), [
            'code' => $account->code,
            'name' => $account->name,
            'account_category_id' => $account->account_category_id,
            'parent_id' => $account->parent_id,
            'type' => $account->type,
            'show_in_ap' => '1',
            'opening_balance' => $account->opening_balance,
        ]);

        $response->assertRedirect(route('erp.accounting.coa'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'type' => 'Income',
            'show_in_ap' => 1,
        ]);

        $this->get(route('erp.payables.vendor-bills.create'))
            ->assertOk()
            ->assertSee($account->code.' - '.$account->name);
    }
}
