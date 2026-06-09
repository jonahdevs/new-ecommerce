<?php

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Pending Reviews')] class extends Component
{
    public function mount(): void
    {
        \Artesaos\SEOTools\Facades\SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function purchasedProducts(): Collection
    {
        $user = auth()->user();

        $rows = $user->orders()
            ->where('status', OrderStatus::COMPLETED->value)
            ->with(['items.product.images' => fn ($q) => $q->where('is_cover', true)->limit(1)])
            ->get()
            ->flatMap(fn ($order) => $order->items->map(fn ($item) => [
                'product'      => $item->product,
                'delivered_at' => $order->delivered_at ?? $order->updated_at,
            ]))
            ->filter(fn ($row) => $row['product'] !== null)
            ->unique(fn ($row) => $row['product']->id)
            ->values();

        $reviewedIds = Review::where('user_id', $user->id)
            ->whereIn('product_id', $rows->pluck('product.id'))
            ->get()
            ->keyBy('product_id');

        return $rows->map(fn ($row) => [
            ...$row,
            'review' => $reviewedIds->get($row['product']->id),
        ]);
    }
}; ?>

<div class="page-fade space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Pending Reviews</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">Pending Reviews</flux:heading>
        <flux:text class="mt-1">Products you've purchased. Share your experience or update an existing review.</flux:text>
    </div>

    @if ($this->purchasedProducts->isEmpty())
        <flux:card class="py-14 text-center">
            <flux:icon.star variant="outline" class="mx-auto size-9 text-ink-4" />
            <flux:heading size="sm" class="mt-4">No purchases yet</flux:heading>
            <flux:text class="mt-1">Once you complete an order, your products will appear here for review.</flux:text>
            <flux:button :href="route('catalog')" variant="primary" class="mt-5" wire:navigate>Browse products</flux:button>
        </flux:card>
    @else
        <flux:card class="overflow-hidden p-0">
            <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns>
                    <flux:table.column>Product</flux:table.column>
                    <flux:table.column class="hidden sm:table-cell">Purchased</flux:table.column>
                    <flux:table.column class="hidden md:table-cell">Rating</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->purchasedProducts as $row)
                        @php
                            $product   = $row['product'];
                            $review    = $row['review'];
                            $cover     = $product->images->first();
                            $hasReview = $review !== null;
                        @endphp
                        <flux:table.row wire:key="product-{{ $product->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($cover)
                                        <img src="{{ Storage::url($cover->path) }}"
                                             alt="{{ $product->name }}"
                                             class="hidden size-10 shrink-0 rounded border border-zinc-100 object-contain p-0.5 sm:block">
                                    @else
                                        <div class="hidden size-10 shrink-0 items-center justify-center rounded border border-zinc-100 bg-surface-sunken sm:flex">
                                            <flux:icon.photo class="size-4 text-zinc-300" />
                                        </div>
                                    @endif
                                    <flux:text class="line-clamp-2 font-semibold text-ink">{{ $product->name }}</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="hidden sm:table-cell">
                                <flux:text size="sm">{{ $row['delivered_at']?->format('d M Y') ?? '—' }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="hidden md:table-cell">
                                @if ($hasReview)
                                    <div class="flex items-center gap-0.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <flux:icon.star variant="{{ $i <= $review->rating ? 'solid' : 'outline' }}"
                                                            class="size-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-zinc-300' }}" />
                                        @endfor
                                    </div>
                                @else
                                    <flux:text size="sm" class="text-ink-4">—</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if (!$hasReview)
                                    <flux:badge color="zinc" size="sm" inset="top bottom">Not reviewed</flux:badge>
                                @elseif ($review->status === ReviewStatus::PENDING)
                                    <flux:badge color="amber" size="sm" inset="top bottom">Pending approval</flux:badge>
                                @else
                                    <flux:badge color="green" size="sm" inset="top bottom">Published</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm"
                                             variant="{{ $hasReview ? 'ghost' : 'primary' }}"
                                             :href="route('account.reviews.form', $product->slug)"
                                             wire:navigate>
                                    {{ $hasReview ? 'Update' : 'Write review' }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

</div>
