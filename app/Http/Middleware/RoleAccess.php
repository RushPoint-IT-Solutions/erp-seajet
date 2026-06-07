<?php

namespace App\Http\Middleware;

use Closure;

class RoleAccess
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/login');
        }

        if (empty($roles) || in_array($user->role ?? 'viewer', $roles)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this ERP area.');
    }
}
