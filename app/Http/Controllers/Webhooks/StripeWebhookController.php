<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $gateway): Response
    {
        $gateway->handleWebhook($request);

        return response('OK', 200);
    }
}
