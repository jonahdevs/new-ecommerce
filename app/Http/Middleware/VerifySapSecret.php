<?php

namespace App\Http\Middleware;

use App\Services\Sap\SapConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySapSecret
{
    public function __construct(private readonly SapConfig $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->config->webhookSecret();

        $provided = (string) $request->header('X-SAP-Secret', '');

        if (empty($secret) || ! hash_equals($secret, $provided)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
