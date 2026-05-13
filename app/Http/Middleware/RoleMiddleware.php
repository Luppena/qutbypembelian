<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = (string) ($user?->role ?? '');
        $normalizedRole = $role === 'admin' ? 'super_admin' : $role;

        if (! $user || ! in_array($normalizedRole, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini. Silakan hubungi administrator.');
        }

        return $next($request);
    }
}
