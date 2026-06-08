<?php

namespace App\Http\Middleware;

use Closure;

class UserAccessControl
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();
        $allowedRoles = empty($roles) ? ['admin'] : $roles;

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        $userRole = strtolower((string) ($user->role ?? 'viewer'));
        $allowedRoles = array_map('strtolower', $allowedRoles);

        if (in_array($userRole, $allowedRoles, true)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this ERP area.');
    }
}
