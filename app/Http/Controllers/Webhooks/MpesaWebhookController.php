<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\MpesaGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaWebhookController extends Controller
{
    public function __invoke(Request $request, MpesaGateway $gateway)
    {
        // Log webhook received
        activity()
            ->withProperties([
                'gateway' => 'mpesa',
                'ip' => $request->ip(),
                'payload' => $request->all(),
                'timestamp' => now(),
            ])
            ->log('webhook_received_mpesa');

        Log::info('Webhook received', [
            'gateway' => 'mpesa',
            'ip' => $request->ip(),
        ]);

        $gateway->handleWebhook($request);

        // M-Pesa requires a specific JSON response shape
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
