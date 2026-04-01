<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sap\SapProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SapProductSyncController extends Controller
{
    public function __construct(
        private readonly SapProductSyncService $syncService
    ) {
    }

    /**
     * Batch sync multiple products from SAP Business One for HANA.
     *
     * SAP calls this endpoint to update product prices and stock quantities.
     * Validates the secret header, processes all products, and returns detailed results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            if (!$this->validateSignature($request)) {
                Log::warning('SAP batch product sync rejected — invalid secret', [
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook secret'
                ], 401);
            }

            $payload = $request->json()->all();
            $products = $payload['products'] ?? [];

            if (empty($products)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products provided',
                ], 422);
            }

            $results = $this->syncService->batchSyncProducts($products);

            return response()->json([
                'success' => true,
                'message' => 'Batch sync completed',
                'total' => count($products),
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'results' => $results['details'],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('SAP batch product sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate signature from SAP
     *
     * @param Request $request
     * @return bool
     */
    private function validateSignature(Request $request): bool
    {
        $secret = config('sap.webhook_secret');

        // If no secret configured, skip validation (not recommended for production)
        if (empty($secret)) {
            return true;
        }

        $providedSecret = $request->header('X-SAP-Secret');

        if (!$providedSecret) {
            return false;
        }

        return hash_equals($secret, $providedSecret);
    }
}
