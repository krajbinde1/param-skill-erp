<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        $allowed = [
            'filament.admin.auth.logout',
            'filament.admin.pages.change-password',
            'filament.admin.auth.login',
        ];

        if (in_array($routeName, $allowed, true)) {
            return $next($request);
        }

        if ($request->routeIs('filament.admin.auth.*')) {
            return $next($request);
        }

        return redirect()->route('filament.admin.pages.change-password');
    }
}
