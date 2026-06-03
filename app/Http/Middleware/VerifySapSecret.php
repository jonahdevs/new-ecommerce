<?php

namespace App\Http\Middleware;

use App\Settings\IntegrationSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySapSecret
{
    public function __construct(private IntegrationSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->settings->sap_webhook_secret;

        if (empty($secret) || $request->header('X-SAP-Secret') !== $secret) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
