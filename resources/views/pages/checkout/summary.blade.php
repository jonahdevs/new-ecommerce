<?php

use App\Services\CartService;
use App\Services\CheckoutSession;
use Livewire\Attributes\{Computed, Layout};
use Livewire\Component;
use App\Services\Payment\PaymentService;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.checkout')] class extends Component {
    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $cartService = app(CartService::class);
        $checkoutSession = app(CheckoutSession::class);

        if (!$cartService->hasItems()) {
            $this->redirectRoute('cart', navigate: true);
            return;
        }

        // No address in session → fall back to default → none means no addresses at all
        if (!$this->address) {
            $this->redirectRoute('checkout.addresses.create', navigate: true);
            return;
        }

        if (!$checkoutSession->hasShipping()) {
            $this->redirectRoute('checkout.shipping', navigate: true);
            return;
        }

        // Only require payment method selection for custom gateway (individual mode)
        if ($this->isCustomGateway && !$checkoutSession->hasPaymentMethod()) {
            $this->redirectRoute('checkout.payment-methods', navigate: true);
            return;
        }
    }

    #[Computed]
    public function address(): ?\App\Models\Address
    {
        $user = auth()->user();
        $checkoutSession = app(CheckoutSession::class);

        $addressId = $checkoutSession->getAddressId() ?? $user->addresses()->where('is_default', true)->value('id');

        if (!$addressId) {
            return null;
        }

        return \App\Models\Address::with(['county', 'area', 'shippingZone'])
            ->where('id', $addressId)
            ->where('user_id', $user->id)
            ->first();
    }

    #[Computed]
    public function shipping(): ?array
    {
        return app(CheckoutSession::class)->getShipping();
    }

    #[Computed]
    public function isCustomGateway(): bool
    {
        return app(PaymentService::class)->isCustom();
    }

    public function changeShipping()
    {
        $this->redirectRoute('checkout.shipping', navigate: true);
    }
}; ?>

<div>
    {{-- Breadcrumb --}}
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Checkout</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Checkout Summary</x-slot:heading>

    {{-- Address --}}
    <flux:card class="mb-4 p-0">
        <div class="px-4 py-2 border-b dark:border-zinc-600 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                <flux:heading level="3" class="font-medium!">Delivery Address</flux:heading>
            </div>
            <flux:link :href="route('checkout.addresses.index')" wire:navigate class="text-xs!">
                Change <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
            </flux:link>
        </div>

        <div class="px-4 py-4">
            @if ($this->address)
                <flux:heading class="text-sm! sm:text-base!">{{ $this->address->full_name }}</flux:heading>
                <div class="mt-2 space-y-1 text-xs! sm:text-sm! text-zinc-500">
                    <flux:text>{{ $this->address->address }}</flux:text>
                    <flux:text>
                        {{ implode(', ', array_filter([$this->address->area?->name, $this->address->county?->name])) }}
                        · {{ format_phone($this->address->phone_number) }}
                    </flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Shipping method --}}
    <flux:card class="mb-4 p-0">
        <div class="px-4 py-2 border-b dark:border-zinc-600 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                <flux:heading level="3" class="font-medium!">Shipping Method</flux:heading>
            </div>
            <flux:link href="#" wire:click.prevent="changeShipping" class="text-xs!">
                Change <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
            </flux:link>
        </div>

        <div class="px-4 py-4">
            @if ($this->shipping)
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading class="text-sm! sm:text-base!">{{ $this->shipping['method_name'] }}</flux:heading>
                        <flux:text class="text-xs! sm:text-sm! text-zinc-500 mt-1">
                            {{ $this->shipping['delivery_window'] }}
                            @if ($this->shipping['station_name'])
                                · Pickup: {{ $this->shipping['station_name'] }}
                            @endif
                        </flux:text>
                    </div>
                    <span class="font-semibold text-xs! sm:text-sm!">
                        @if ($this->shipping['cost'] == 0)
                            <span class="text-green-600 font-medium text-xs! sm:text-sm!">Free</span>
                        @else
                            {{ format_currency($this->shipping['cost']) }}
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Payment method - only show for custom gateway (individual mode) --}}
    @if ($this->isCustomGateway)
        <flux:card class="p-0 mb-4">
            <div class="px-4 py-2 border-b dark:border-zinc-600 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                    <flux:heading level="3" class="font-medium!">Payment Method</flux:heading>
                </div>
                <flux:link :href="route('checkout.payment-methods')" wire:navigate class="text-xs!">
                    Change <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
                </flux:link>
            </div>
            <div class="px-4 py-4">
                <flux:heading class="text-sm! sm:text-base!">
                    {{ app(\App\Services\CheckoutSession::class)->getPaymentMethod() === 'card' ? 'Card' : 'M-Pesa' }}
                </flux:heading>
                <flux:text class="text-xs! sm:text-sm! text-zinc-500 mt-1">
                    {{ app(\App\Services\CheckoutSession::class)->getPaymentMethod() === 'card'
                        ? 'Visa, Mastercard, Amex'
                        : 'STK push to your phone' }}
                </flux:text>
            </div>
        </flux:card>
    @endif

    <flux:link :href="route('shop.index')" wire:navigate class="text-xs">
        ← Continue shopping
    </flux:link>
</div>
