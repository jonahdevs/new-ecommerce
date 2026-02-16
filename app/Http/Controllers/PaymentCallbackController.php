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
    ) {}

    /**
     * Handle successful payment callback
     */
    public function handleSuccess(Request $request)
    {
        Log::info(
            'Pesawise Callback Received: ' .
                json_encode([
                    'method' => $request->method(), // ✅ Add this
                    'all_params' => $request->all(),
                    'query_params' => $request->query(),
                    'body' => $request->getContent(), // ✅ Also log raw body
                    'url' => $request->fullUrl(),
                ], JSON_PRETTY_PRINT)
        );


        if ($request->isMethod('post')) {
            return $this->handleWebhook($request);
        }

        return $this->handleUserRedirect($request);
    }


    public function handleSuccessPost(Request $request)
    {
        Log::info('Pesawise POST Callback Received', [
            'all_params' => $request->all(),
            'query_params' => $request->query(),
            'url' => $request->fullUrl(),
        ]);
    }

    /**
     * Handle POST webhook from Pesawise server
     */
    private function handleWebhook(Request $request)
    {
        Log::info('Pesawise POST Callback Received', [
            'all_params' => $request->all(),
            'query_params' => $request->query(),
            'url' => $request->fullUrl(),
        ]);
        try {
            $data = $request->json()->all();

            $status = $data['status'] ?? null;
            $externalId = $data['externalId'] ?? null; // This is your order reference
            $pesawiseOrderId = $data['orderId'] ?? null;
            $amount = $data['amount'] ?? null;

            Log::info('Processing Pesawise webhook', [
                'status' => $status,
                'externalId' => $externalId,
                'orderId' => $pesawiseOrderId,
                'amount' => $amount,
            ]);

            if (!$externalId) {
                Log::error('Webhook missing externalId');
                return response()->json(['error' => 'Missing externalId'], 400);
            }

            $order = Order::where('reference', $externalId)->first();

            if (!$order) {
                Log::error('Order not found for webhook', ['externalId' => $externalId]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            if ($status === 'SUCCESS') {
                $this->processSuccessfulPayment($order, $pesawiseOrderId, $amount);
            } else {
                Log::warning('Payment webhook with non-SUCCESS status', [
                    'status' => $status,
                    'order_id' => $order->id,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Handle GET redirect when user clicks "Continue"
     */
    private function handleUserRedirect(Request $request)
    {
        // Get order from query parameters (that we set in buildPayload)
        $orderId = $request->query('order_id');
        $reference = $request->query('reference');

        Log::info('Processing user redirect', [
            'order_id' => $orderId,
            'reference' => $reference,
        ]);

        if (!$orderId || !$reference) {
            Log::error('Missing order parameters in redirect');

            return redirect()->route('customer.orders.index')
                ->with('info', 'Payment processing. You will receive a confirmation email shortly.');
        }

        $order = Order::where('id', $orderId)
            ->where('reference', $reference)
            ->first();

        if (!$order) {
            Log::error('Order not found for redirect', [
                'order_id' => $orderId,
                'reference' => $reference,
            ]);

            return redirect()->route('customer.orders.index')
                ->with('error', 'Order not found.');
        }

        // Check if order is already paid (webhook might have already processed it)
        if ($order->payment_status === 'paid') {
            return redirect()->route('customer.orders.show', $order)
                ->with('success', 'Payment successful! Your order is being processed.');
        }

        // If not paid yet, mark as pending verification
        // The webhook will handle the actual confirmation
        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'pending_verification',
            ]);
        }

        return redirect()->route('customer.orders.show', $order)
            ->with('info', 'Payment is being verified. You will receive a confirmation shortly.');
    }

    /**
     * Handle payment cancellation
     */
    public function handleCancel(Request $request)
    {
        Log::info('Pesawise Cancellation Callback', [
            'query' => $request->query(),
        ]);

        $orderId = $request->query('order_id');
        $reference = $request->query('reference');

        if ($orderId && $reference) {
            $order = Order::where('id', $orderId)
                ->where('reference', $reference)
                ->first();

            if ($order) {
                DB::transaction(function () use ($order) {
                    $order->update([
                        'payment_status' => 'cancelled',
                        'status' => 'cancelled',
                    ]);

                    $order->payment->update([
                        'status' => 'cancelled',
                    ]);
                });

                Log::info('Order cancelled', [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                ]);

                return redirect()->route('checkout.index')
                    ->with('error', 'Payment was cancelled. Please try again.');
            }
        }

        return redirect()->route('checkout.index')
            ->with('info', 'Payment was cancelled.');
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(Order $order, ?string $pesawiseOrderId, ?int $amount)
    {
        DB::transaction(function () use ($order, $pesawiseOrderId, $amount) {
            // Update order
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            // Update payment record
            $order->payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'meta' => array_merge($order->payment->meta ?? [], [
                    'pesawise_order_id' => $pesawiseOrderId,
                    'paid_amount' => $amount,
                    'confirmed_at' => now()->toISOString(),
                ]),
            ]);

            Log::info('Payment processed successfully', [
                'order_id' => $order->id,
                'reference' => $order->reference,
                'pesawise_order_id' => $pesawiseOrderId,
                'amount' => $amount,
            ]);

            // Clear session data
            session()->forget([
                'pesawise_payment_order_id',
                'pesawise_payment_reference',
                'pesawise_payment_started_at',
            ]);

            // Dispatch events (uncomment as needed)
            // event(new OrderPaid($order));
            // Mail::to($order->user)->send(new OrderConfirmationMail($order));
        });
    }
}
