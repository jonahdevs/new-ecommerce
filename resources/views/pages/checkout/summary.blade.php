<?php

use App\Services\CartService;
use App\Services\CheckoutSession;
use Livewire\Attributes\{Computed, Layout};
use Livewire\Component;
use App\Services\Payment\PaymentService;

new #[Layout('layouts.checkout')] class extends Component {
    public function mount(): void
    {
        $cartService = app(CartService::class);
        $checkoutSession = app(CheckoutSession::class);

        if (!$cartService->hasItems()) {
            $this->redirectRoute('cart', navigate: true);
            return;
        }

        if (auth()->user()->addresses()->doesntExist()) {
            $this->redirectRoute('checkout.addresses.create', navigate: true);
            return;
        }

        if (!$checkoutSession->hasShipping()) {
            $this->redirectRoute('checkout.shipping', navigate: true);
            return;
        }

        // If custom gateway and no payment method chosen yet, go to payment page
        if (app(PaymentService::class)->isCustom() && !$checkoutSession->hasPaymentMethod() && $checkoutSession->getShipping()['method_type'] !== 'quote') {
            $this->redirectRoute('checkout.payment-methods', navigate: true);
            return;
        }
    }

    #[Computed]
    public function address()
    {
        $checkoutSession = app(CheckoutSession::class);
        $user = auth()->user();

        $addressId = $checkoutSession->getAddressId() ?? ($user->addresses()->where('is_default', true)->value('id') ?? $user->addresses()->oldest()->value('id'));

        return \App\Models\Address::with(['county', 'area', 'shippingZone'])->find($addressId);
    }

    #[Computed]
    public function shipping(): ?array
    {
        return app(CheckoutSession::class)->getShipping();
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

    {{-- Quote notice banner — only shown when customer selected quote --}}
    @if ($this->shipping && $this->shipping['method_type'] === 'quote')
        <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg mb-4">
            <flux:icon.information-circle class="size-5 shrink-0 mt-0.5 text-amber-500" />
            <div class="text-sm">
                <p class="font-medium text-amber-800">Delivery outside Nairobi</p>
                <p class="text-amber-700 mt-0.5">
                    No payment is taken today. Our team will contact you
                    with a delivery cost before your order is confirmed.
                </p>
            </div>
        </div>
    @endif

    {{-- Address --}}
    <flux:card class="mb-4 p-0">
        <div class="px-4 py-2 border-b flex items-center justify-between">
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
                <flux:heading>{{ $this->address->full_name }}</flux:heading>
                <div class="mt-2 space-y-1 text-sm text-zinc-500">
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
        <div class="px-4 py-2 border-b flex items-center justify-between">
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
                        <flux:heading>{{ $this->shipping['method_name'] }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 mt-1">
                            {{ $this->shipping['delivery_window'] }}
                            @if ($this->shipping['station_name'])
                                · Pickup: {{ $this->shipping['station_name'] }}
                            @endif
                        </flux:text>
                    </div>
                    <span class="font-semibold text-sm">
                        @if ($this->shipping['method_type'] === 'quote')
                            <span class="text-amber-500 font-medium text-sm">TBD</span>
                        @elseif($this->shipping['cost'] == 0)
                            <span class="text-green-600 font-medium text-sm">Free</span>
                        @else
                            {{ format_currency($this->shipping['cost']) }}
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Payment method --}}
    @if (!$this->shipping || $this->shipping['method_type'] !== 'quote')
        <flux:card class="p-0 mb-4">
            <div class="px-4 py-2 border-b flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                    <flux:heading level="3" class="font-medium!">Payment Method</flux:heading>
                </div>
                <flux:link :href="route('checkout.payment-methods')" wire:navigate class="text-xs!">
                    Change <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
                </flux:link>
            </div>
            <div class="px-4 py-4">
                <flux:heading>
                    {{ app(\App\Services\CheckoutSession::class)->getPaymentMethod() === 'card' ? 'Card' : 'M-Pesa' }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-500 mt-1">
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
