<?php

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\OrderItem;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('My Account')] class extends Component
{
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function openOrdersCount(): int
    {
        return auth()->user()->orders()
            ->whereNotIn('status', [OrderStatus::COMPLETED->value, OrderStatus::CANCELLED->value])
            ->count();
    }

    #[Computed]
    public function pendingQuotesCount(): int
    {
        return auth()->user()->quotes()
            ->where('status', QuoteStatus::AWAITING_APPROVAL->value)
            ->count();
    }

    #[Computed]
    public function wishlistCount(): int
    {
        return StorefrontSession::wishlistCount();
    }

    #[Computed]
    public function pendingReviewsCount(): int
    {
        $user = auth()->user();

        $reviewedIds = $user->reviews()->pluck('product_id');

        return OrderItem::select('order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', OrderStatus::COMPLETED->value)
            ->whereNotIn('order_items.product_id', $reviewedIds)
            ->distinct()
            ->count();
    }

    #[Computed]
    public function defaultAddress()
    {
        return auth()->user()->addresses()->where('is_default', true)->first();
    }
}; ?>

<div class="page-fade space-y-8">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Account</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    {{-- KPIs --}}
    @php
        $kpis = [
            ['icon' => 'shopping-bag',  'label' => 'Open Orders',     'value' => $this->openOrdersCount,     'color' => 'text-blue-500',    'bg' => 'bg-blue-50'],
            ['icon' => 'document-text', 'label' => 'Pending Quotes',  'value' => $this->pendingQuotesCount,  'color' => 'text-amber-500',   'bg' => 'bg-amber-50'],
            ['icon' => 'heart',         'label' => 'Wishlist',        'value' => $this->wishlistCount,       'color' => 'text-rose-500',    'bg' => 'bg-rose-50'],
            ['icon' => 'star',          'label' => 'Pending Reviews', 'value' => $this->pendingReviewsCount, 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'],
        ];
    @endphp
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($kpis as $kpi)
            <flux:card class="p-5">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wide text-ink-3">{{ $kpi['label'] }}</span>
                    <div class="flex size-8 items-center justify-center rounded-md {{ $kpi['bg'] }}">
                        <flux:icon :icon="$kpi['icon']" variant="outline" class="size-4 {{ $kpi['color'] }}" />
                    </div>
                </div>
                <div class="mt-3 font-serif text-4xl font-normal text-ink">{{ $kpi['value'] }}</div>
            </flux:card>
        @endforeach
    </div>

    {{-- Account Details + Address Book --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Account Details --}}
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3">
                <div class="flex items-center gap-2">
                    <flux:icon.user variant="outline" class="size-4 text-zinc-600" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Account Details</flux:heading>
                </div>
                <a href="{{ route('profile.edit') }}" wire:navigate
                   class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-brand-500 transition hover:opacity-70">
                    <flux:icon.cog-6-tooth class="size-3.5" />
                    Edit
                </a>
            </div>

            <div class="p-5">
                <div class="mb-4 flex items-center gap-3.5 border-b border-zinc-100 pb-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div>
                        <div class="text-[15px] font-bold text-ink">{{ auth()->user()->name }}</div>
                        <div class="text-[11px] text-ink-3">Member since {{ auth()->user()->created_at->format('F Y') }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="mb-0.5 text-[10px] font-bold uppercase tracking-wide text-ink-4">Email Address</div>
                    <div class="text-[13px] font-semibold text-ink">{{ auth()->user()->email }}</div>
                </div>

                <div>
                    <div class="mb-0.5 text-[10px] font-bold uppercase tracking-wide text-ink-4">Phone Number</div>
                    <div class="text-[13px] font-semibold text-ink">{{ auth()->user()->phone ?? 'Not set' }}</div>
                </div>
            </div>
        </flux:card>

        {{-- Address Book --}}
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3">
                <div class="flex items-center gap-2">
                    <flux:icon.map-pin variant="outline" class="size-4 text-zinc-600" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Address Book</flux:heading>
                </div>
                <a href="{{ route('account.addresses.index') }}" wire:navigate
                   class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-brand-500 transition hover:opacity-70">
                    <flux:icon.chevron-right class="size-3.5" />
                    Manage
                </a>
            </div>

            <div class="p-5">
                @if ($this->defaultAddress)
                    <div class="mb-3">
                        <flux:badge color="lime" size="sm">Default</flux:badge>
                    </div>
                    <div class="mb-3">
                        <div class="mb-0.5 text-[10px] font-bold uppercase tracking-wide text-ink-4">Shipping Address</div>
                        <div class="text-[13px] font-semibold text-ink">{{ $this->defaultAddress->name }}</div>
                    </div>
                    <div class="text-[12px] leading-relaxed text-ink-3">
                        {{ $this->defaultAddress->line1 }}<br>
                        {{ $this->defaultAddress->phone }}
                    </div>
                @else
                    <p class="mb-4 text-[13px] italic text-ink-3">No default address set.</p>
                    <flux:button variant="primary" size="sm" :href="route('account.addresses.index')" wire:navigate>
                        <flux:icon.plus class="size-3.5" />
                        Add Address
                    </flux:button>
                @endif
            </div>
        </flux:card>

    </div>

    {{-- Quick Settings --}}
    <flux:card class="overflow-hidden p-0">
        <div class="flex items-center gap-2 border-b border-zinc-200 px-5 py-3">
            <flux:icon.cog-6-tooth variant="outline" class="size-4 text-zinc-600" />
            <flux:heading size="sm" class="uppercase tracking-wide">Settings</flux:heading>
        </div>

        @php
            $settingsLinks = [
                ['icon' => 'user',         'label' => 'Profile Settings',      'sub' => 'Update your personal information',      'route' => 'profile.edit'],
                ['icon' => 'lock-closed',  'label' => 'Password & Security',   'sub' => 'Change password and manage 2FA',        'route' => 'security.edit'],
                ['icon' => 'bell',         'label' => 'Notifications',         'sub' => 'Manage email and WhatsApp preferences', 'route' => 'notifications.edit'],
                ['icon' => 'shield-check', 'label' => 'Privacy & Data',        'sub' => 'Control your data and privacy settings','route' => 'privacy.edit'],
            ];
        @endphp

        @foreach ($settingsLinks as $link)
            <a href="{{ route($link['route']) }}" wire:navigate
               class="flex items-center gap-4 border-b border-zinc-100 px-5 py-3.5 transition hover:bg-zinc-50 last:border-0">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-zinc-100">
                    <flux:icon :icon="$link['icon']" variant="outline" class="size-4 text-zinc-500" />
                </div>
                <div>
                    <div class="text-[13px] font-bold text-ink">{{ $link['label'] }}</div>
                    <div class="text-[11px] text-ink-3">{{ $link['sub'] }}</div>
                </div>
                <flux:icon.chevron-right class="ml-auto size-4 shrink-0 text-zinc-300" />
            </a>
        @endforeach
    </flux:card>

</div>
