<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\SapSyncRequest;
use App\Jobs\ProcessSapProductSync;
use App\Settings\IntegrationSettings;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SapSyncController extends Controller
{
    public function __construct(private IntegrationSettings $settings) {}

    public function __invoke(SapSyncRequest $request): JsonResponse
    {
        if (! $this->settings->sap_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'SAP sync is disabled.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $products = $request->validated('products');

        ProcessSapProductSync::dispatch($products);

        return response()->json([
            'success' => true,
            'message' => 'Sync queued successfully.',
            'total' => count($products),
        ], Response::HTTP_ACCEPTED);
    }
}
