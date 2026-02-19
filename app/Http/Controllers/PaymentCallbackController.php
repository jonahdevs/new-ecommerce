<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Payment Callback Controller for Pesawise Integration
 * 
 * Handles two types of callbacks:
 * 1. POST webhook - Pesawise sends payment status (processed first)
 * 2. GET redirect - User is redirected after clicking "Continue" button
 */
class PaymentCallbackController extends Controller
{


    public function handleSuccess(Request $request)
    {
        Log::info('=== PAYMENT CALLBACK RECEIVED ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_data' => $request->all(),
            'raw_body' => $request->getContent(),
        ]);

        if ($request->isMethod('post')) {
            return $this->handleWebhook($request);
        }

        return $this->handleUserRedirect($request);
    }

    /**
     * Handle payment cancellation
     */
    public function handleCancel(Request $request)
    {
        $reference = session('pesawise_payment_reference');

        if ($reference) {
            $order = Order::where('reference', $reference)->first();

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
            }
        }

        // Clear session regardless
        $this->clearPaymentSession();

        return $this->breakOutOfIframe(
            route('checkout.summary', [], true) . '?cancelled=1'
        );
    }


    private function handleWebhook(Request $request)
    {
        Log::info('=== POST WEBHOOK PROCESSING ===');

        try {
            $data = $request->json()->all();

            log::info('Webhook payload: ' . json_encode($data, JSON_PRETTY_PRINT));

            $status = $data['status'] ?? null;
            $externalId = $data['externalId'] ?? null;
            $orderId = $data['orderId'] ?? null;

            if (!$status || !$externalId) {
                Log::error('Webhook missing required fields', compact('status', 'externalId'));
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            $order = Order::where('reference', $externalId)->first();

            if (!$order) {
                Log::error('Order not found for webhook', compact('externalId', 'orderId'));
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Process based on status
            if ($status === 'SUCCESS') {
                $this->processSuccessfulPayment($order, $orderId, $data);
            } else {
                Log::warning('Webhook received with non-SUCCESS status', [
                    'status' => $status,
                    'order_id' => $order->id,
                ]);

                $order->update(['payment_status' => 'failed']);

                $order->payment->update([
                    'status' => 'failed',
                    'meta' => [
                        ...($order->payment->meta ?? []),
                        'webhook_status' => $status,
                        'webhook_received_at' => now()->toISOString(),
                    ],
                ]);
            }

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Always 200 so Pesawise doesn't keep retrying
            return response()->json(['status' => 'error'], 200);
        }
    }

    /**
     * Handle GET redirect when user clicks "Continue"
     */
    private function handleUserRedirect()
    {
        $reference = session('pesawise_payment_reference');

        if (!$reference) {
            return $this->breakOutOfIframe(route('checkout.summary'));
        }

        $order = Order::where('reference', $reference)->first();

        if (!$order) {
            return $this->breakOutOfIframe(route('checkout.summary'));
        }

        session([
            'payment_success_order_id' => $order->id,
            'payment_success_expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        $this->clearPaymentSession();

        return $this->breakOutOfIframe(route('checkout.success-page'));
    }

    private function breakOutOfIframe(string $url): \Illuminate\Http\Response
    {
        return response(
            "<script>window.top.location.href = " . json_encode($url) . ";</script>",
            200,
            ['Content-Type' => 'text/html']
        );
    }

    private function processSuccessfulPayment(Order $order, ?string $pesawiseOrderId, array $webhookData)
    {
        if ($order->payment_status === 'paid') {
            Log::info('Payment already processed, skipping', [
                'order_id' => $order->id,
            ]);
            return;
        }

        DB::transaction(function () use ($order, $pesawiseOrderId, $webhookData) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            $order->payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'meta' => [
                    ...($order->payment->meta ?? []),
                    'pesawise_order_id' => $pesawiseOrderId,
                    'webhook_data' => $webhookData,
                    'confirmed_at' => now()->toISOString(),
                ],
            ]);

            app(CartService::class)->clear($order->user);
        });

        Log::info('Payment processed successfully', [
            'order_id' => $order->id,
            'reference' => $order->reference,
        ]);
    }

    private function clearPaymentSession()
    {
        session()->forget([
            'pesawise_payment_url',
            'pesawise_payment_reference',
        ]);
    }
}
