<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Sap\SapWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SapWebhookController extends Controller
{
    /**
     * Receives inbound webhook callbacks from SAP Business One.
     *
     * SAP calls this endpoint when the eTIMS device finishes KRA
     * validation and a CU number is ready. The handler validates the
     * secret header, updates the order, and triggers receipt generation.
     *
     * Always returns 200 on success so SAP doesn't retry unnecessarily.
     * Secret validation failures return 401. Processing errors bubble as 500 so
     * SAP knows to retry.
     */
    public function __invoke(Request $request, SapWebhookHandler $handler): Response
    {
        $handler->handle($request);

        return response('OK', 200);
    }
}
