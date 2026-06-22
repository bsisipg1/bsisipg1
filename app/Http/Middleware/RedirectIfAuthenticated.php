<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            $user = auth()->guard($guard)->user();

            if ($user && $user->role === UserRole::Admin) {
                return redirect()->route('admin.dashboard');
            }
        }

        return $next($request);
    }
}
