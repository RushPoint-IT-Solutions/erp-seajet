<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RbacAccessTest extends TestCase
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
    }

    public function test_accounts_payable_role_is_limited_to_ap_modules_and_dashboard_data()
    {
        $this->actingAsRole('accounts_payable');

        $this->get(route('erp.dashboard'))
            ->assertOk()
            ->assertSee('Accounts Payable')
            ->assertDontSee('Accounts Receivable');
        $this->get(route('erp.expenses.index'))->assertOk();
        $this->get(route('erp.customer-billing.aging'))->assertStatus(403);
        $this->post(route('erp.payables.payments.approve', 999999))->assertStatus(404);
        $this->get(route('erp.invoices.index'))->assertStatus(403);
        $this->get(route('erp.customers.index'))->assertStatus(403);
    }

    public function test_accounts_receivable_role_is_limited_to_ar_modules_and_dashboard_data()
    {
        $this->actingAsRole('accounts_receivable');

        $this->get(route('erp.dashboard'))
            ->assertOk()
            ->assertSee('Accounts Receivable')
            ->assertDontSee('Accounts Payable');
        $this->get(route('erp.invoices.index'))->assertOk();
        $this->get(route('erp.payments.create'))->assertOk();
        $this->get(route('erp.customer-billing.aging'))->assertOk();
        $this->get(route('erp.reports.financial-statements'))->assertStatus(403);
        $this->get(route('erp.expenses.index'))->assertStatus(403);
        $this->get(route('erp.suppliers.index'))->assertStatus(403);
    }

    public function test_admin_can_save_dynamic_role_permissions_and_the_middleware_enforces_them()
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $companyId,
            'name' => 'AR Viewer',
            'slug' => 'ar_viewer',
            'description' => 'Custom test role',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsAdmin();
        $this->get(route('erp.users.index'))
            ->assertOk()
            ->assertSee('Accounts Receivable (AR)')
            ->assertSee('Accounts Payable (AP)')
            ->assertSee('Save Permissions');
        $this->put(route('erp.users.roles.permissions.update', $roleId), [
            'permissions' => [
                'dashboard' => ['view' => '1'],
                'invoices' => ['view' => '1'],
            ],
        ])->assertRedirect(route('erp.users.index'));

        $this->assertSame(2, DB::table('permission_role')->where('role_id', $roleId)->count());

        $this->actingAsRole('ar_viewer', $companyId);
        $this->get(route('erp.dashboard'))->assertOk();
        $this->get(route('erp.invoices.index'))->assertOk();
        $this->get(route('erp.payments.create'))->assertStatus(403);
    }

    public function test_inactive_default_role_does_not_fall_back_to_configured_permissions()
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        DB::table('roles')->where('company_id', $companyId)->where('slug', 'accounts_payable')->update(['is_active' => false]);

        $this->actingAsRole('accounts_payable', $companyId);
        $this->get(route('erp.dashboard'))->assertStatus(403);
    }

    public function test_user_without_dashboard_access_is_redirected_to_first_accessible_module()
    {
        $companyId = DB::table('companies')->orderBy('id')->value('id');
        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $companyId,
            'name' => 'AP Viewer',
            'slug' => 'ap_viewer',
            'description' => 'Payables access without dashboard access',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = DB::table('permissions')->where('slug', 'payables.view')->value('id');
        DB::table('permission_role')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);

        $this->actingAsRole('ap_viewer', $companyId);

        $this->get(route('erp.dashboard'))->assertRedirect(route('erp.expenses.index'));
        $this->get(route('erp.expenses.index'))->assertOk();
        $this->get(route('erp.invoices.index'))->assertStatus(403);
    }

    public function test_system_update_screen_is_administrator_only()
    {
        $this->actingAsAdmin();
        $this->get(route('erp.system-update.index'))
            ->assertOk()
            ->assertSee('System Version &amp; Updates', false)
            ->assertSee('git pull --ff-only')
            ->assertSee('composer install');

        $this->actingAsRole('accounts_payable');
        $this->get(route('erp.system-update.index'))->assertStatus(403);
        $this->post(route('erp.system-update.run'), ['confirmation' => 'UPDATE'])->assertStatus(403);
    }

    private function actingAsRole($role, $companyId = null)
    {
        $user = new User();
        $user->id = 900;
        $user->name = 'RBAC '.$role;
        $user->email = $role.'@seajet.test';
        $user->role = $role;
        $user->company_id = $companyId ?: DB::table('companies')->orderBy('id')->value('id');
        $user->is_active = true;
        $this->actingAs($user);

        return $user;
    }
}
