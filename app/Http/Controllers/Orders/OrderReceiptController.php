<?php

namespace App\Http\Controllers\Orders;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderReceiptController extends Controller
{
    public function __invoke(Order $order): Response
    {
        // Only order owner can download
        abort_if($order->user_id !== auth()->id(), 403);

        // Only downloadable if paid
        abort_if(
            $order->payment?->status !== PaymentStatus::PAID->value,
            403,
            'Receipt only available for paid orders.'
        );

        $order->load(['items.product', 'payment', 'user']);

        $pdf = Pdf::loadView('pdf.orders-receipt', ['order' => $order])
            ->setPaper('a4', 'portrait');

        return $pdf->download("receipt-{$order->reference}.pdf");
    }
}
