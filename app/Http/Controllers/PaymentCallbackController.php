<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Enhanced Payment Callback Controller with security and proper error handling
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Handle successful payment callback from Pesawise
     * Simply redirect to success page - let Livewire handle the payment processing
     */
    public function handleSuccess(Request $request)
    {
        $orderId = $request->query('order_id')
            ?? session('pesawise_payment_order_id');
        $reference = $request->query('reference')
            ?? session('pesawise_payment_reference');

        // Validation: Must have identifiers
        if (!$orderId || !$reference) {
            return redirect()->route('checkout.summary')
                ->with('error', 'Payment session expired. Please contact support if payment was deducted.');
        }

        try {
            $order = Order::where('id', $orderId)
                ->where('reference', $reference)
                ->with('payment')
                ->firstOrFail();

            // Generate a temporary success token (expires in 5 minutes)
            $successToken = Str::random(32);

            session([
                'payment_success_token' => $successToken,
                'payment_success_order_id' => $order->id,
                'payment_success_expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            // Clear payment initiation session
            session()->forget([
                'pesawise_payment_order_id',
                'pesawise_payment_reference',
                'pesawise_payment_started_at'
            ]);

            Log::info('Payment success validated', [
                'order_id' => $order->id,
                'reference' => $order->reference,
            ]);

            app(CartService::class)->clear();

            // Redirect to success page WITHOUT order ID in URL
            return redirect()->route('checkout.success-page', [
                'token' => $successToken // ← Only token in URL, not order ID
            ]);

        } catch (\Exception $e) {
            Log::error('Payment success callback error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('checkout.summary')
                ->with('error', 'Order not found. Please contact support.');
        }

    }

    /**
     * Handle payment cancellation callback from Pesawise
     */
    public function handleCancel(Request $request)
    {
        //  Try query params first, fallback to session
        $orderId = $request->query('order_id')
            ?? session('pesawise_payment_order_id');
        $reference = $request->query('reference')
            ?? session('pesawise_payment_reference');

        // Try to mark order as cancelled (best effort)
        if ($orderId && $reference) {
            try {
                $order = Order::where('id', $orderId)
                    ->where('reference', $reference)
                    ->first();

                if ($order && $order->payment) {
                    $order->payment->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);

                    Log::info('Payment cancelled', [
                        'order_id' => $order->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error marking payment as cancelled', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear all payment sessions
        session()->forget([
            'pesawise_payment_order_id',
            'pesawise_payment_reference',
            'pesawise_payment_started_at'
        ]);

        return redirect()->route('checkout.summary')
            ->with('info', 'Payment was cancelled. You can try again when ready.');
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
