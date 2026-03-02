<?php

use App\Services\CartService;
use App\Services\CheckoutSession;
use Livewire\Attributes\{Computed, Layout};
use Livewire\Component;

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
            $this->redirectRoute('customer.address-book.create', navigate: true);
            return;
        }

        if (!$checkoutSession->hasShipping()) {
            $this->redirectRoute('checkout.shipping', navigate: true);
            return;
        }

        // If custom gateway and no payment method chosen yet, go to payment page
        if (app(\App\Services\Payment\PaymentService::class)->isCustom() && !session('checkout.payment_method')) {
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

    public function changeShipping(): mixed
    {
        return $this->redirectRoute('checkout.shipping', navigate: true);
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
    <div class="border rounded-sm bg-white mb-4">
        <div class="px-4 py-2 border-b flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                <flux:heading level="3" class="font-medium!">Delivery Address</flux:heading>
            </div>
            <flux:link :href="route('checkout.addresses')" wire:navigate class="text-xs!">
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
    </div>

    {{-- Shipping method --}}
    <div class="border rounded-sm bg-white mb-4">
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
                        {{ $this->shipping['cost'] == 0 ? 'Free' : format_currency($this->shipping['cost']) }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    <flux:link :href="route('products')" wire:navigate class="text-xs">
        ← Continue shopping
    </flux:link>
</div>
