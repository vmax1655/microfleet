<?php

namespace App\Http\Middleware;

use App\Support\Rbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRbac
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $routeName = $request->route()?->getName();
        if (Rbac::moduleKeyForRoute($routeName) === null) {
            return $next($request);
        }

        if (! Rbac::allowsRoute($user->role, $routeName, 'view')) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
