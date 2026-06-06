<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Sap\SapWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SapWebhookController extends Controller
{
    public function __invoke(Request $request, SapWebhookHandler $handler): JsonResponse
    {
        $handler->handle($request);

        // Always return 200 so SAP does not retry on processing errors.
        // Retries on auth failures (401) are handled inside the handler via abort().
        return response()->json(['success' => true]);
    }
}
