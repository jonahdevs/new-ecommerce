<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the admin panel to any user that has been assigned at least one role.
 * Customers have no roles, so they are blocked regardless of which custom roles
 * an admin creates — no code changes needed when new roles are added.
 */
class EnsureIsStaffMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->roles->isEmpty()) {
            abort(403);
        }

        return $next($request);
    }
}
