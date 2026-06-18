<?php

use App\Models\RecentlyViewed;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Recently Viewed')] class extends Component
{
    public function mount(): void
    {
        \Artesaos\SEOTools\Facades\SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function products(): Collection
    {
        return auth()->user()
            ->recentlyViewed()
            ->with(['product' => fn ($q) => $q->with([
                'images' => fn ($q) => $q->where('is_cover', true)->limit(1),
                'brand',
            ])])
            ->limit(24)
            ->get()
            ->pluck('product')
            ->filter();
    }
}; ?>

<div class="page-fade space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Recently Viewed</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">Recently Viewed</flux:heading>
        <flux:text class="mt-1">Products you've browsed — pick up where you left off.</flux:text>
    </div>

    @if ($this->products->isEmpty())
        <div class="rounded-md border border-zinc-200 bg-surface-sunken p-8 text-center sm:p-12">
            <flux:icon.eye variant="outline" class="mx-auto size-8 text-zinc-300" />
            <div class="mt-3 font-serif text-xl text-ink">Nothing here yet</div>
            <p class="mt-1 text-sm text-ink-3">Start browsing and products you view will appear here.</p>
            <flux:button :href="route('catalog')" variant="primary" class="mt-5" wire:navigate>Browse products</flux:button>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($this->products as $product)
                <x-storefront.product-card :product="$product" wire:key="rv-{{ $product->id }}" />
            @endforeach
        </div>
    @endif

</div>
