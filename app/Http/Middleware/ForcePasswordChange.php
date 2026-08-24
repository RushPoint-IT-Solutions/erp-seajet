<?php

namespace App\Http\Middleware;

use Closure;

class ForcePasswordChange
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->must_change_password) {
            return $next($request);
        }

        $allowed = ['password.change', 'password.update', 'logout'];
        if (in_array(optional($request->route())->getName(), $allowed, true)) {
            return $next($request);
        }

        return redirect()->route('password.change')
            ->with('warning', 'Change the temporary password before using the accounting system.');
    }
}
