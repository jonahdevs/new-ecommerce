<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\PesawiseGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PesawiseWebhookController extends Controller
{
    public function __invoke(Request $request, PesawiseGateway $gateway): Response
    {
        Log::info('Webhook received', [
            'gateway' => 'pesawise',
            'ip' => $request->ip(),
        ]);
        $gateway->handleWebhook($request);

        return response('OK', 200);
    }
}
