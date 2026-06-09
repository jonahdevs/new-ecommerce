<?php

namespace App\Http\Controllers\Account;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = Auth::user();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'member_since' => $user->created_at->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'addresses' => $user->addresses()
                ->get(['label', 'name', 'phone', 'line1', 'is_default', 'created_at'])
                ->toArray(),
            'orders' => $user->orders()
                ->with('items:order_id,product_snapshot,quantity,unit_price_cents,line_total_cents')
                ->get(['id', 'order_number', 'status', 'total_cents', 'currency', 'payment_method', 'created_at'])
                ->map(fn ($order) => [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total_cents' => $order->total_cents,
                    'currency' => $order->currency,
                    'payment_method' => $order->payment_method,
                    'placed_at' => $order->created_at->toIso8601String(),
                    'items' => $order->items->map(fn ($item) => [
                        'product' => data_get($item->product_snapshot, 'name'),
                        'quantity' => $item->quantity,
                        'unit_price_cents' => $item->unit_price_cents,
                    ])->toArray(),
                ])->toArray(),
            'quotes' => $user->quotes()
                ->get(['quote_number', 'status', 'total_cents', 'currency', 'created_at'])
                ->map(fn ($q) => [
                    'quote_number' => $q->quote_number,
                    'status' => $q->status,
                    'total_cents' => $q->total_cents,
                    'currency' => $q->currency,
                    'submitted_at' => $q->created_at->toIso8601String(),
                ])->toArray(),
            'reviews' => $user->reviews()
                ->with('product:id,name,slug')
                ->get(['product_id', 'rating', 'title', 'body', 'status', 'created_at'])
                ->map(fn ($r) => [
                    'product' => $r->product?->name,
                    'rating' => $r->rating,
                    'title' => $r->title,
                    'body' => $r->body,
                    'status' => $r->status,
                    'written_at' => $r->created_at->toIso8601String(),
                ])->toArray(),
        ];

        $filename = 'my-data-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            fn () => print json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
