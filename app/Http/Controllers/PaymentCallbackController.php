<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Enhanced Payment Callback Controller with security and proper error handling
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private InventoryService $inventoryService
    ) {}

    /**
     * Handle successful payment callback from Pesawise
     * Simply redirect to success page - let Livewire handle the payment processing
     */
    public function success(Request $request)
    {
        $correlationId = uniqid('callback_success_', true);

        Log::info('=== PESAWISE CALLBACK RECEIVED ===', [
            'correlation_id' => $correlationId,
            'timestamp' => now()->toISOString(),
            'url' => $request->fullUrl(),
            'data' => $request->all(),
        ]);

        // Simply redirect to success page
        return redirect()->route('checkout.success');
    }

    /**
     * Handle payment cancellation callback from Pesawise
     */
    public function cancel(Request $request)
    {
        $correlationId = uniqid('callback_cancel_', true);

        Log::info('Pesawise cancel callback received', [
            'correlation_id' => $correlationId,
            'data' => $request->all(),
        ]);

        // Try to get order_id from our appended URL parameter
        $notificationId = $request->get('order_id')
            ?? $request->get('notificationId')
            ?? $request->query('order_id');

        if ($notificationId) {
            $order = Order::find($notificationId);

            if ($order && $order->status === 'pending') {
                $this->markPaymentFailed($order, 'Payment cancelled by user', $correlationId);
            }
        }

        return redirect()->route('checkout.summary')
            ->with('error', 'Payment was cancelled. Please try again.');
    }

    /**
     * Webhook endpoint for server-to-server notifications (if Pesawise supports)
     * This is more secure than relying solely on redirect callbacks
     */
    public function webhook(Request $request)
    {
        $correlationId = uniqid('webhook_', true);

        Log::info('Pesawise webhook received', [
            'correlation_id' => $correlationId,
            'data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // TODO: Verify webhook signature
        // if (!$this->verifyWebhookSignature($request)) {
        //     Log::error('Invalid webhook signature', [
        //         'correlation_id' => $correlationId,
        //     ]);
        //     return response()->json(['error' => 'Invalid signature'], 403);
        // }

        $orderId = $request->get('orderId');
        $status = $request->get('status');
        $notificationId = $request->get('notificationId');

        if (!$notificationId) {
            return response()->json(['error' => 'Missing notificationId'], 400);
        }

        // Process webhook asynchronously for better reliability
        // dispatch(new ProcessPesawiseWebhook($request->all(), $correlationId));

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Mark payment as failed and release inventory
     */
    private function markPaymentFailed(Order $order, string $reason, string $correlationId): void
    {
        try {
            DB::transaction(function () use ($order, $reason, $correlationId) {
                $this->orderService->markPaymentAsFailed($order, $reason);
                $this->inventoryService->releaseReservation($order);
            });

            Log::info('Payment marked as failed', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark payment as failed', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify webhook signature from Pesawise
     * 
     * @param Request $request
     * @return bool
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        // TODO: Implement based on Pesawise documentation
        // Example implementation:

        // $signature = $request->header('X-Pesawise-Signature');
        // $payload = $request->getContent();
        // $secret = config('services.pesawise.webhook_secret');

        // $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // return hash_equals($expectedSignature, $signature);

        return true; // Placeholder
    }

    /**
     * Make server-to-server call to verify payment status
     * 
     * @param string $orderId
     * @return array|null
     */
    private function verifyPaymentWithPesawise(string $orderId): ?array
    {
        // TODO: Implement server-to-server verification
        // This is more secure than trusting redirect callbacks

        // $response = Http::withHeaders([
        //     'api-key' => config('services.pesawise.api_key'),
        //     'api-secret' => config('services.pesawise.api_secret'),
        // ])->get(config('services.pesawise.api_url') . "/orders/{$orderId}");

        // return $response->json();

        return null; // Placeholder
    }
}
