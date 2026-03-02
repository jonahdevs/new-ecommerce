<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class CallbackController extends Controller
{
    public function success(Request $request, PaymentService $paymentService)
    {
        // Pesawise sends externalId (= order reference) back on success
        $reference = $request->input('externalId') ?? $request->input('reference');

        if ($reference) {
            $order = Order::where('reference', $reference)->first();
            $status = $paymentService->verify($reference);

            if ($status->isPaid && $order) {
                return redirect()->route('customer.orders.show', $order)
                    ->with('success', 'Payment successful! Your order has been placed.');
            }
        }

        return redirect()->route('checkout.summary')
            ->with('warning', 'Payment status could not be confirmed. Please check your orders.');
    }

    public function cancel(Request $request)
    {
        return redirect()->route('checkout.summary')
            ->with('warning', 'Payment was cancelled. Your order has not been placed.');
    }
}
