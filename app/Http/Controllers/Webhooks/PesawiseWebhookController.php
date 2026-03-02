<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\Gateways\PesawiseGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PesawiseWebhookController extends Controller
{
    public function __invoke(Request $request, PesawiseGateway $gateway): Response
    {
        $gateway->handleWebhook($request);

        return response('OK', 200);
    }
}
