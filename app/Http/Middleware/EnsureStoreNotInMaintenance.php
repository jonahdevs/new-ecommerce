<?php

namespace App\Http\Middleware;

use App\Settings\MaintenanceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shows the storefront maintenance page when {@see MaintenanceSettings} is on.
 * The admin panel and all authentication routes stay reachable, and signed-in
 * admins keep full access so staff can work while the store is closed.
 */
class EnsureStoreNotInMaintenance
{
    public function __construct(private MaintenanceSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->maintenance_mode) {
            return $next($request);
        }

        // Keep the admin panel + auth flows reachable so staff can sign in.
        if ($request->is('admin', 'admin/*', 'login', 'logout', 'register', 'forgot-password', 'reset-password/*', 'two-factor-challenge', 'user/*', 'email/*')) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'message' => $this->settings->maintenance_message,
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
