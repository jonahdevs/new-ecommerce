<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function success(Request $request)
    {
        // Log everything Pesawise sends
        Log::info('Pesawise success callback', $request->all());

        $notificationId = $request->get('notificationId'); // Your order ID
        $transactionId = $request->get('transactionId');
        $status = $request->get('status');

        if (!$notificationId) {
            Log::error('Payment callback missing notificationId', $request->all());
            return redirect()->route('home')->with('error', 'Invalid payment callback');
        }

        $order = Order::find($notificationId);

        if (!$order) {
            Log::error('Order not found for payment callback', [
                'notificationId' => $notificationId,
                'request' => $request->all()
            ]);
            return redirect()->route('home')->with('error', 'Order not found');
        }

        // Verify payment status is successful
        if ($status !== 'COMPLETED' && $status !== 'SUCCESS') {
            Log::warning('Payment callback with non-success status', [
                'order_id' => $order->id,
                'status' => $status,
                'request' => $request->all()
            ]);
            return redirect()->route('home')->with('error', 'Payment not completed');
        }

        // Mark order as paid
        app(OrderService::class)->markAsPaid($order, [
            'transactionId' => $transactionId,
            'status' => $status,
            'callback_data' => $request->all(),
        ]);

        Log::info('Order marked as paid', ['order_id' => $order->id]);

        

        return redirect()->route('home')
            ->with('success', 'Payment successful! Your order has been confirmed.');
    }

    public function cancel(Request $request)
    {
        Log::info('Pesawise cancel callback', $request->all());

        $notificationId = $request->get('notificationId');

        if ($notificationId) {
            $order = Order::find($notificationId);

            if ($order) {
                app(OrderService::class)->markPaymentAsFailed($order, 'Payment cancelled by user');
                Log::info('Order payment marked as failed', ['order_id' => $order->id]);
            }
        }

        return redirect()->route('cart')
            ->with('error', 'Payment was cancelled. Please try again.');
    }
}
