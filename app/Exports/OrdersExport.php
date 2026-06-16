<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $filterStatus = '',
        private readonly string $dateFrom = '',
        private readonly string $dateTo = '',
    ) {}

    public function query()
    {
        return Order::query()
            ->with(['user', 'latestPayment'])
            ->withCount('items')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->dateFrom !== '' && $this->dateTo !== '', fn ($q) => $q->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]))
            ->latest();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Order #', 'Customer Name', 'Customer Email',
            'Items', 'Subtotal (KES)', 'Delivery (KES)', 'VAT (KES)', 'Total (KES)',
            'Payment Status', 'Order Status', 'Placed At',
        ];
    }

    /** @param Order $order */
    public function map($order): array
    {
        return [
            $order->order_number,
            $order->user?->name,
            $order->user?->email,
            $order->items_count,
            round($order->subtotal_cents / 100, 2),
            round($order->delivery_cents / 100, 2),
            round($order->vat_cents / 100, 2),
            round($order->total_cents / 100, 2),
            $order->latestPayment?->status->label() ?? 'Unpaid',
            $order->status->label(),
            $order->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /** @return array<string, int> */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 28,
            'C' => 32,
            'D' => 8,
            'E' => 16,
            'F' => 16,
            'G' => 14,
            'H' => 16,
            'I' => 16,
            'J' => 16,
            'K' => 18,
        ];
    }
}
