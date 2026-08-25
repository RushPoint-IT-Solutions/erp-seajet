<?php

namespace App\Services\Security;

use App\Models\Role;
use Illuminate\Support\Facades\Schema;

class RbacService
{
    public function allows($user, $module, $action = 'view')
    {
        if (!$user) {
            return false;
        }

        $slug = $this->normalizeRole($user->role ?? 'viewer');
        if ($slug === 'admin') {
            return true;
        }

        $permission = strtolower($module.'.'.$action);
        $role = $this->roleForUser($user, $slug);
        if ($role) {
            return $role->permissions->contains('slug', $permission);
        }

        if ($this->roleExistsForUser($user, $slug)) {
            return false;
        }

        // Legacy/test users may exist before the RBAC data migration is run.
        return $this->matchesDefaults($slug, $permission);
    }

    public function normalizeRole($role)
    {
        $slug = strtolower(trim((string) $role));
        $slug = str_replace([' ', '-'], '_', $slug);

        return config('rbac.role_aliases.'.$slug, $slug);
    }

    public function roleForUser($user, $slug = null)
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions') || !Schema::hasTable('permission_role')) {
            return null;
        }

        $slug = $slug ?: $this->normalizeRole($user->role ?? 'viewer');
        $companyId = $user->company_id ?? null;

        if ($companyId) {
            $companyRole = Role::with('permissions')->where('slug', $slug)->where('company_id', $companyId)->first();
            if ($companyRole) {
                return $companyRole->is_active ? $companyRole : null;
            }
        }

        return Role::with('permissions')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereNull('company_id')
            ->first();
    }

    public function moduleDefinitions()
    {
        return config('rbac.modules', []);
    }

    private function roleExistsForUser($user, $slug)
    {
        if (!Schema::hasTable('roles')) {
            return false;
        }

        $companyId = $user->company_id ?? null;
        return Role::where('slug', $slug)->where(function ($query) use ($companyId) {
            $query->whereNull('company_id');
            if ($companyId) {
                $query->orWhere('company_id', $companyId);
            }
        })->exists();
    }

    private function matchesDefaults($role, $permission)
    {
        $grants = config('rbac.roles.'.$role.'.permissions', []);
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
