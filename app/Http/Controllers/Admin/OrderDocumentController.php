<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderDocumentService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDocumentController extends Controller
{
    public function __construct(private readonly OrderDocumentService $documents) {}

    public function packingList(Order $order): StreamedResponse
    {
        abort_unless(
            in_array($order->status->value, ['out_for_delivery', 'completed']),
            403,
            'Packing list is only available once the order is out for delivery.',
        );

        return $this->documents->streamPackingList($order)
            ?? abort(500, 'Could not generate packing list.');
    }

    public function deliveryNote(Order $order): StreamedResponse
    {
        abort_unless(
            in_array($order->status->value, ['out_for_delivery', 'completed']),
            403,
            'Delivery note is only available once the order is out for delivery.',
        );

        return $this->documents->streamDeliveryNote($order)
            ?? abort(500, 'Could not generate delivery note.');
    }

    public function kraReceipt(Order $order): StreamedResponse
    {
        abort_unless((bool) $order->kra_receipt_path, 404);
        abort_unless(Storage::disk('local')->exists($order->kra_receipt_path), 404);

        return Storage::disk('local')->response(
            $order->kra_receipt_path,
            $order->order_number.'-receipt.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
