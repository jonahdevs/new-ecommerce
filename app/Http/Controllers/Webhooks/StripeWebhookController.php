<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $gateway): Response
    {
        // Fix 1 — correct gateway label
        Log::info('Webhook received', [
            'gateway' => 'stripe',
            'ip' => $request->ip(),
        ]);

        // Fix 2 — catch unexpected exceptions
        // abort(400) from signature failure is intentional and still propagates
        try {
            $gateway->handleWebhook($request);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handler threw exception', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response('Server Error', 500);
        }

        return response('OK', 200);
    }
}
