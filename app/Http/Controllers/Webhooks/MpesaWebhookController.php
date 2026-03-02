<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\MpesaGateway;
use Illuminate\Http\Request;

class MpesaWebhookController extends Controller
{
    public function __invoke(Request $request, MpesaGateway $gateway)
    {
        $gateway->handleWebhook($request);

        // M-Pesa requires a specific JSON response shape
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
