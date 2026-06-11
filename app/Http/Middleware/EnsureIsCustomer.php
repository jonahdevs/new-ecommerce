<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the customer self-service area (/account/*) for customers only. Staff
 * members (any user with at least one role) are sent to the admin dashboard, so
 * knowing a URL — or following a stale link — can't land them on the customer
 * account pages, which are scoped to their own, usually empty, account.
 */
class EnsureIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'roles') && $user->roles->isNotEmpty()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
