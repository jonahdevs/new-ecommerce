<?php

namespace App\Http\Controllers\Dev;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Local-only previews for the transactional email templates.
 *
 * Maizzle's dev server can only show un-rendered Blade (it has no PHP runtime),
 * so these routes render each mail view with factory-built models to show the
 * real, data-filled result in the browser. Sample rows are created inside a
 * transaction that is always rolled back, so nothing is persisted.
 */
class MailPreviewController extends Controller
{
    /**
     * Templates available to preview, each mapped to a builder that returns
     * the view name and the exact view data its notification passes.
     *
     * @return array<string, array{label: string, build: callable(): array{0: string, 1: array<string, mixed>}}>
     */
    private function templates(): array
    {
        return [
            'order-confirmation' => [
                'label' => 'Order — Confirmation',
                'build' => function (): array {
                    [$user, $order] = $this->order(OrderStatus::PROCESSING, ['payment_method' => 'mpesa']);
                    OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

                    return ['mails.orders.confirmation', [
                        'order' => $order->load('items'),
                        'customerName' => $user->name,
                        'paymentLabel' => 'M-Pesa',
                        'orderUrl' => route('account.orders.show', $order),
                    ]];
                },
            ],
            'order-status-update' => [
                'label' => 'Order — Status update',
                'build' => function (): array {
                    [$user, $order] = $this->order(OrderStatus::OUT_FOR_DELIVERY);
                    OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

                    return ['mails.orders.status-update', [
                        'order' => $order->load('items'),
                        'customerName' => $user->name,
                        'newStatus' => $order->status,
                    ]];
                },
            ],
            'order-refund-processed' => [
                'label' => 'Order — Refund processed',
                'build' => function (): array {
                    [$user, $order] = $this->order(OrderStatus::REFUNDED);

                    return ['mails.orders.refund-processed', [
                        'order' => $order,
                        'customerName' => $user->name,
                        'refundAmount' => 250000,
                        'refundReason' => 'Item out of stock',
                        'orderUrl' => route('account.orders.show', $order),
                    ]];
                },
            ],
            'order-kra-invoice' => [
                'label' => 'Order — KRA tax invoice',
                'build' => function (): array {
                    [$user, $order] = $this->order(OrderStatus::COMPLETED, ['kra_cu_number' => 'KRACU0123456789']);

                    return ['mails.orders.kra-invoice', [
                        'order' => $order,
                        'customerName' => $user->name,
                        'orderUrl' => route('account.orders.show', $order),
                    ]];
                },
            ],
            'quote-received' => [
                'label' => 'Quote — Request received',
                'build' => function (): array {
                    [$user, $quote] = $this->quote(QuoteStatus::DRAFT, [
                        'delivery_required' => true,
                        'notes' => 'Please confirm lead time for the cold room panels before we proceed.',
                    ]);
                    QuoteItem::factory()->count(3)->create(['quote_id' => $quote->id]);

                    return ['mails.quotes.received', [
                        'quote' => $quote->load('items'),
                        'customerName' => $user->name,
                        'quotationsUrl' => route('account.quotes.index'),
                    ]];
                },
            ],
            'quote-sent' => [
                'label' => 'Quote — Ready for review',
                'build' => function (): array {
                    [$user, $quote] = $this->quote(QuoteStatus::AWAITING_APPROVAL, ['total_cents' => 500000]);
                    QuoteItem::factory()->count(3)->create(['quote_id' => $quote->id]);

                    return ['mails.quotes.sent', [
                        'quote' => $quote->load('items'),
                        'customerName' => $user->name,
                        'portalUrl' => route('account.quotes.show', $quote),
                    ]];
                },
            ],
            'quote-expiring' => [
                'label' => 'Quote — Expiring soon',
                'build' => function (): array {
                    [$user, $quote] = $this->quote(QuoteStatus::AWAITING_APPROVAL, [
                        'total_cents' => 500000,
                        'expires_at' => now()->addDays(3),
                    ]);
                    QuoteItem::factory()->count(3)->create(['quote_id' => $quote->id]);

                    return ['mails.quotes.expiring', [
                        'quote' => $quote->load('items'),
                        'customerName' => $user->name,
                        'daysLeft' => 3,
                        'portalUrl' => route('account.quotes.show', $quote),
                    ]];
                },
            ],
        ];
    }

    /**
     * Render an index of every previewable template.
     */
    public function index(): View
    {
        abort_unless(app()->environment('local'), 404);

        $links = collect($this->templates())
            ->map(fn (array $t, string $key): array => ['key' => $key, 'label' => $t['label']])
            ->values()
            ->all();

        return view('dev.mail-preview', ['links' => $links]);
    }

    /**
     * Render a single template with sample data, discarding the sample rows.
     */
    public function show(string $template): Response
    {
        abort_unless(app()->environment('local'), 404);

        $templates = $this->templates();
        abort_unless(isset($templates[$template]), 404);

        DB::beginTransaction();

        try {
            [$view, $data] = $templates[$template]['build']();

            // The Invoice-Pro design is the default (mails/*). The previous
            // navy/red design is preserved under mails/classic/* and rendered
            // on demand for comparison.
            if (request('design') === 'classic') {
                $view = Str::replaceFirst('mails.', 'mails.classic.', $view);
            }

            // render() is eager, so the data is read before the rollback below.
            return response(view($view, $data)->render());
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Build a sample order with an owner.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: Order}
     */
    private function order(OrderStatus $status, array $attributes = []): array
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            ...$attributes,
        ]);

        return [$user, $order];
    }

    /**
     * Build a sample quote with an owner.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: Quote}
     */
    private function quote(QuoteStatus $status, array $attributes = []): array
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            ...$attributes,
        ]);

        return [$user, $quote];
    }
}
