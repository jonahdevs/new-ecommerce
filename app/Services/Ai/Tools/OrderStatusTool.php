<?php

namespace App\Services\Ai\Tools;

use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\Auth;

/**
 * Looks up the signed-in customer's own orders and quotes.
 *
 * SECURITY: every query is scoped to Auth::id() at the data layer — the model
 * cannot widen it. Guests get an "ask them to sign in" note and no data. The
 * `reference` argument only filters within the current user's own records.
 */
class OrderStatusTool implements Tool
{
    private const LIMIT = 5;

    public function name(): string
    {
        return 'check_my_orders';
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => "Look up the currently signed-in customer's OWN recent orders and quotes "
                    .'(status, total, dates and a link to each). Use this for questions like "where is my order", '
                    .'"order status", "my quote" or "has my quote been approved". Only returns data for an '
                    .'authenticated customer; for guests it reports that they must sign in.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reference' => [
                            'type' => 'string',
                            'description' => 'Optional order or quote number to narrow the results, e.g. "ORD-1024" or "QUO-0007".',
                        ],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    public function handle(array $arguments): string
    {
        $user = Auth::user();

        if (! $user) {
            return $this->encode([
                'authenticated' => false,
                'note' => 'The customer is not signed in. Ask them to sign in to view their orders and quotes, '
                    .'or to contact the team on +254 713 777 111.',
            ]);
        }

        $reference = trim((string) ($arguments['reference'] ?? ''));

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->when($reference !== '', fn ($q) => $q->where('order_number', 'like', "%{$reference}%"))
            ->latest()
            ->take(self::LIMIT)
            ->get();

        $quotes = Quote::query()
            ->where('user_id', $user->id)
            ->when($reference !== '', fn ($q) => $q->where('quote_number', 'like', "%{$reference}%"))
            ->latest()
            ->take(self::LIMIT)
            ->get();

        return $this->encode([
            'authenticated' => true,
            'orders' => $orders->map(fn (Order $order) => [
                'number' => $order->order_number,
                'status' => $order->status->label(),
                'total' => money($order->total_cents),
                'placed' => $order->created_at?->toFormattedDateString(),
                'url' => route('account.orders.show', $order),
            ])->all(),
            'quotes' => $quotes->map(fn (Quote $quote) => [
                'number' => $quote->quote_number,
                'status' => $quote->status->label(),
                'total' => money($quote->total_cents),
                'expires' => $quote->expires_at?->toFormattedDateString(),
                'url' => route('account.quotes.show', $quote),
            ])->all(),
            'note' => 'Share the relevant order/quote with its status and link. If both lists are empty, say nothing was found.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
