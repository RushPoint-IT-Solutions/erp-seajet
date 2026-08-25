<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RbacProvisioner
{
    public function provision(array $companyIds = null)
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('permission_role')) {
            return;
        }

        $now = now();
        $permissionIds = [];
        foreach (config('rbac.modules', []) as $modules) {
            foreach ($modules as $module => $definition) {
                foreach ($definition['actions'] as $action) {
                    $slug = $module.'.'.$action;
                    DB::table('permissions')->updateOrInsert(
                        ['slug' => $slug],
                        ['module' => $module, 'action' => $action, 'updated_at' => $now, 'created_at' => $now]
                    );
                    $permissionIds[$slug] = DB::table('permissions')->where('slug', $slug)->value('id');
                }
            }
        }

        if ($companyIds === null) {
            $companyIds = Schema::hasTable('companies') ? DB::table('companies')->pluck('id')->all() : [];
        }
        if (empty($companyIds)) {
            $companyIds = [null];
        }

        foreach ($companyIds as $companyId) {
            foreach (config('rbac.roles', []) as $slug => $definition) {
                $roleQuery = DB::table('roles')->where('slug', $slug);
                $companyId === null ? $roleQuery->whereNull('company_id') : $roleQuery->where('company_id', $companyId);
                $roleId = $roleQuery->value('id');
                $seedGrants = !$roleId;

                if (!$roleId) {
                    $roleId = DB::table('roles')->insertGetId([
                        'company_id' => $companyId,
                        'name' => $definition['name'],
                        'slug' => $slug,
                        'description' => $definition['description'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                if (!$seedGrants) {
                    continue;
                }

                foreach ($permissionIds as $permissionSlug => $permissionId) {
                    if (!$this->isGranted($definition['permissions'], $permissionSlug)) {
                        continue;
                    }
                    $pivot = ['role_id' => $roleId, 'permission_id' => $permissionId];
                    if (!DB::table('permission_role')->where($pivot)->exists()) {
                        DB::table('permission_role')->insert($pivot);
                    }
                }
            }
        }
    }

    private function isGranted(array $grants, $permission)
    {
        foreach ($grants as $grant) {
            if ($grant === '*' || $grant === $permission) {
                return true;
            }
            if (substr($grant, -2) === '.*' && strpos($permission, substr($grant, 0, -1)) === 0) {
                return true;
            }
        }

        return false;
    }
}
