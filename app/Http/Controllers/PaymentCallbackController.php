<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function success(Request $request)
    {
        // Pesawise will send payment data as query parameters
        // You need to check their documentation for exact parameter names

        $notificationId = $request->get('notificationId'); // This is your order ID
        $transactionId = $request->get('transactionId'); // Pesawise transaction ID
        $status = $request->get('status'); // Payment status

        if (!$notificationId) {
            dd('Invalid payment callback');
            return redirect()->route('home')->with('error', 'Invalid payment callback');
        }

        $order = Order::find($notificationId);

        if (!$order) {
            dd('Order not found');
            return redirect()->route('home')->with('error', 'Order not found');
        }

        // Mark order as paid
        app(OrderService::class)->markAsPaid($order, [
            'transactionId' => $transactionId,
            'status' => $status,
            'callback_data' => $request->all(),
        ]);

        dd('Payment successful! Your order has been confirmed.');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment successful! Your order has been confirmed.');
    }

    public function cancel(Request $request)
    {
        $notificationId = $request->get('notificationId');

        if ($notificationId) {
            $order = Order::find($notificationId);

            if ($order) {
                app(OrderService::class)->markPaymentAsFailed($order, 'Payment cancelled by user');
            }
        }

        dd('Payment was cancelled. Your order is still pending.');
        return redirect()->route('cart')
            ->with('error', 'Payment was cancelled. Your order is still pending.');
    }
}
