<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Admin) {
            if ($request->expectsJson()) {
                abort(403, 'Admin access required.');
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
