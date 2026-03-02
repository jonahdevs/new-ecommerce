<?php

use App\Models\Address;
use App\Services\CheckoutSession;
use App\Services\Payment\Gateways\CustomGateway;
use App\Services\PaymentService;
use Livewire\Attributes\{Computed, Layout, Locked};
use Livewire\Component;

new #[Layout('layouts.checkout')] class extends Component {
    public string $paymentMethod = 'mpesa'; // mpesa | card

    public function mount(): void
    {
        $checkoutSession = app(CheckoutSession::class);

        // Guard: no shipping selected yet
        if (!$checkoutSession->hasShipping()) {
            $this->redirectRoute('checkout.shipping', navigate: true);
            return;
        }

        // Restore previously chosen method if returning to this page
        $this->paymentMethod = CustomGateway::getPaymentMethod();
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    #[Computed]
    public function address(): ?Address
    {
        $checkoutSession = app(CheckoutSession::class);
        $user = auth()->user();

        $addressId = $checkoutSession->getAddressId() ?? ($user->addresses()->where('is_default', true)->value('id') ?? $user->addresses()->oldest()->value('id'));

        return Address::with(['county', 'area'])->find($addressId);
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

    #[Computed]
    public function activeGateway(): string
    {
        return app(PaymentService::class)->activeGateway();
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function updatedPaymentMethod(): void
    {
        CustomGateway::setPaymentMethod($this->paymentMethod);
    }

    public function confirm(): mixed
    {
        // For custom gateway, persist the chosen method
        if ($this->isCustomGateway) {
            CustomGateway::setPaymentMethod($this->paymentMethod);
        }

        return $this->redirectRoute('checkout.summary', navigate: true);
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
            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>
                Checkout
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Payment Methods</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Payment</x-slot:heading>

    {{-- 1. Address — confirmed, read-only --}}
    <div class="border rounded-sm bg-white mb-4">
        <div class="px-4 py-2 border-b flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                <flux:heading level="3" class="font-medium!">Delivering to</flux:heading>
            </div>
            <flux:link :href="route('checkout.addresses')" wire:navigate class="text-xs!">
                Change
                <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
            </flux:link>
        </div>

        <div class="px-4 py-4">
            @if ($this->address)
                <flux:heading>{{ $this->address->full_name }}</flux:heading>
                <div class="mt-2 space-y-1">
                    <flux:text>{{ $this->address->address }}</flux:text>
                    <flux:text>
                        {{ implode(', ', array_filter([$this->address->area?->name, $this->address->county?->name])) }}
                    </flux:text>
                    <flux:text class="text-zinc-400 text-xs">
                        {{ format_phone($this->address->phone_number) }}
                    </flux:text>
                </div>
            @endif
        </div>
    </div>

    {{-- 2. Shipping — confirmed, read-only --}}
    <div class="border rounded-sm bg-white mb-4">
        <div class="px-4 py-2 border-b flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                <flux:heading level="3" class="font-medium!">Shipping Method</flux:heading>
            </div>
            <flux:link :href="route('checkout.shipping')" wire:navigate class="text-xs!">
                Change
                <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
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
                    <span class="font-semibold text-sm shrink-0">
                        {{ $this->shipping['cost'] == 0 ? 'Free' : format_currency($this->shipping['cost']) }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- 3. Payment method --}}
    <div class="border rounded-sm bg-white mb-4">
        <div class="px-4 py-2 border-b">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" @class([
                    'size-5',
                    'text-green-500' => $this->isCustomGateway
                        ? in_array($paymentMethod, ['mpesa', 'card'])
                        : true,
                    'text-zinc-300' =>
                        $this->isCustomGateway && !in_array($paymentMethod, ['mpesa', 'card']),
                ]) />
                <flux:heading level="3" class="font-medium!">Payment Method</flux:heading>
            </div>
        </div>

        <div class="p-5">
            @if ($this->isCustomGateway)
                {{-- Custom gateway — customer chooses M-Pesa or Card --}}
                <div class="grid grid-cols-2 gap-3">

                    {{-- M-Pesa --}}
                    <label @class([
                        'flex flex-col items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors',
                        'border-zinc-800 bg-zinc-50 ring-1 ring-zinc-800 dark:border-zinc-300 dark:bg-zinc-800' =>
                            $paymentMethod === 'mpesa',
                        'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' =>
                            $paymentMethod !== 'mpesa',
                    ])>
                        <input type="radio" wire:model.live="paymentMethod" value="mpesa" class="sr-only" />
                        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                            {{-- Swap this div with your actual M-Pesa SVG logo --}}
                            <span class="text-green-700 font-bold text-sm">M-Pesa</span>
                        </div>
                        <div class="text-center">
                            <p class="font-medium text-sm">M-Pesa</p>
                            <p class="text-xs text-zinc-500 mt-0.5">Pay via STK push</p>
                        </div>
                        @if ($paymentMethod === 'mpesa')
                            <flux:badge color="green" size="sm">Selected</flux:badge>
                        @endif
                    </label>

                    {{-- Card --}}
                    <label @class([
                        'flex flex-col items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors',
                        'border-zinc-800 bg-zinc-50 ring-1 ring-zinc-800 dark:border-zinc-300 dark:bg-zinc-800' =>
                            $paymentMethod === 'card',
                        'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' =>
                            $paymentMethod !== 'card',
                    ])>
                        <input type="radio" wire:model.live="paymentMethod" value="card" class="sr-only" />
                        <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                            <flux:icon.credit-card class="size-7 text-blue-600" />
                        </div>
                        <div class="text-center">
                            <p class="font-medium text-sm">Card</p>
                            <p class="text-xs text-zinc-500 mt-0.5">Visa, Mastercard, Amex</p>
                        </div>
                        @if ($paymentMethod === 'card')
                            <flux:badge color="blue" size="sm">Selected</flux:badge>
                        @endif
                    </label>
                </div>

                {{-- Context hint --}}
                <div class="mt-4 p-3 bg-zinc-50 rounded-md border border-zinc-100">
                    @if ($paymentMethod === 'mpesa')
                        <div class="flex items-start gap-2">
                            <flux:icon.device-phone-mobile class="size-4 text-green-600 shrink-0 mt-0.5" />
                            <flux:text class="text-xs text-zinc-600">
                                After placing your order, you'll receive an M-Pesa STK push on your registered phone
                                number.
                                Enter your PIN to complete payment. The request expires in 60 seconds.
                            </flux:text>
                        </div>
                    @else
                        <div class="flex items-start gap-2">
                            <flux:icon.lock-closed class="size-4 text-blue-600 shrink-0 mt-0.5" />
                            <flux:text class="text-xs text-zinc-600">
                                You'll be taken to a secure card payment form powered by Stripe.
                                Your card details are never stored on our servers.
                            </flux:text>
                        </div>
                    @endif
                </div>
            @else
                {{-- Single gateway — just show what will be used --}}
                <div class="flex items-center gap-3 p-3 bg-zinc-50 rounded-md border border-zinc-100">
                    <flux:icon.credit-card class="size-5 text-zinc-500" />
                    <div>
                        <flux:text class="font-medium text-sm capitalize">
                            {{ $this->activeGateway }}
                        </flux:text>
                        <flux:text class="text-xs text-zinc-500">
                            You'll be redirected to complete payment securely.
                        </flux:text>
                    </div>
                    <flux:icon.check-circle variant="solid" class="size-5 text-green-500 ms-auto" />
                </div>
            @endif

            {{-- Confirm button --}}
            <div class="flex justify-end mt-5">
                <flux:button wire:click="confirm" variant="primary" class="cursor-pointer">
                    Continue to Review
                    <x-slot name="iconTrailing">
                        <flux:icon.chevron-right class="size-4 ms-2" />
                    </x-slot>
                </flux:button>
            </div>
        </div>
    </div>

    <flux:link :href="route('products')" wire:navigate class="text-xs">
        ← Continue shopping
    </flux:link>
</div>
