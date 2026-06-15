<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Paystack\PaystackPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaystackWebhookController extends Controller
{
    public function __construct(private PaystackPaymentService $paystack) {}

    /**
     * Receive and process asynchronous Paystack events (server-to-server).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature', '');

        try {
            $this->paystack->handleWebhook($request->getContent(), $signature);
        } catch (RuntimeException) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return response()->json(['received' => true]);
    }
}
