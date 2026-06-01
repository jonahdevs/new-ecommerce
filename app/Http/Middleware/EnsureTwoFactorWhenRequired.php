<?php

namespace App\Http\Middleware;

use App\Settings\SecuritySettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When {@see SecuritySettings::$require_two_factor} is on, staff reaching the
 * admin panel without two-factor authentication are sent to their security
 * settings to enable it first. The security page itself is outside this
 * middleware group, so there is no redirect loop.
 */
class EnsureTwoFactorWhenRequired
{
    public function __construct(private SecuritySettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->require_two_factor) {
            return $next($request);
        }

        $user = $request->user();

        if ($user
            && method_exists($user, 'hasEnabledTwoFactorAuthentication')
            && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
