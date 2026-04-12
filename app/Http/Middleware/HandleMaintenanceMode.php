<?php

namespace App\Http\Middleware;

use App\Settings\MaintenanceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleMaintenanceMode
{
    public function __construct(private readonly MaintenanceSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->maintenance_mode) {
            return $next($request);
        }

        // Authenticated admins/staff always bypass maintenance
        if ($request->user()?->is_staff) {
            return $next($request);
        }

        // Comma-separated IP whitelist bypass
        if ($this->isAllowedIp($request)) {
            return $next($request);
        }

        // Secret token bypass: ?secret=your-token
        $secret = $this->settings->maintenance_secret;
        if ($secret && $request->query('secret') === $secret) {
            return $next($request);
        }

        $message = $this->settings->maintenance_message
            ?? 'We are performing scheduled maintenance to improve your experience. We will be back shortly — thank you for your patience.';

        return response()->view('errors.503', [
            'maintenanceMessage' => $message,
        ], 503);
    }

    private function isAllowedIp(Request $request): bool
    {
        $allowed = $this->settings->maintenance_allowed_ips;

        if (empty($allowed)) {
            return false;
        }

        $allowedIps = array_filter(array_map('trim', explode(',', $allowed)));

        return in_array($request->ip(), $allowedIps, true);
    }
}
