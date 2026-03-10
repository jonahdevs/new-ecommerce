<?php

use App\Models\Address;
use App\Services\CheckoutSession;
use App\Services\Payment\PaymentService;
use Livewire\Attributes\{Computed, Layout};
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
        $this->paymentMethod = app(CheckoutSession::class)->getPaymentMethod();
    }

    //  Computed

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

    //  Actions

    public function updatedPaymentMethod(): void
    {
        app(CheckoutSession::class)->setPaymentMethod($this->paymentMethod);
    }

    public function confirm()
    {
        // For custom gateway, persist the chosen method
        if ($this->isCustomGateway) {
            app(CheckoutSession::class)->setPaymentMethod($this->paymentMethod);
        }

        $this->redirectRoute('checkout.summary', navigate: true);
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
    <flux:card class="p-0 mb-4">
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
    </flux:card>

    {{-- 2. Shipping — confirmed, read-only --}}
    <flux:card class="p-0 mb-4">
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
    </flux:card>

    {{-- 3. Payment Method Section --}}
    <flux:card class="p-0 mb-4 overflow-hidden">
        <div class="px-4 py-2 border-b">
            <div class="flex items-center gap-2">
                <flux:icon.check-circle variant="solid" @class([
                    'size-5',
                    'text-green-500' => $this->isCustomGateway
                        ? in_array($paymentMethod, ['mpesa', 'card'])
                        : true,
                    'text-zinc-300' =>
                        $this->isCustomGateway && !in_array($paymentMethod, ['mpesa', 'card']),
                ]) />
                <flux:heading level="3" class="font-semibold">Payment Method</flux:heading>
            </div>
        </div>

        <div class="p-0">
            @if ($this->isCustomGateway)
                {{-- We use a standard radio group here without the "cards" variant to keep the radios normal size --}}
                <flux:radio.group wire:model.live="paymentMethod" class="flex flex-col">

                    {{-- M-Pesa Option --}}
                    <label
                        class="group flex items-center justify-between p-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-start gap-4">
                            {{-- Standard Radio --}}
                            <div class="flex items-center h-5 mt-1">
                                <flux:radio value="mpesa" />
                            </div>
                            <div>
                                <flux:label class="font-medium text-zinc-800 dark:text-white cursor-pointer">M-Pesa
                                </flux:label>
                                <flux:description>Pay via STK push directly to your phone.</flux:description>
                            </div>
                        </div>

                        {{-- Right Aligned Logo --}}
                        <div class="shrink-0 ml-4">
                            <div
                                class="w-12 h-12 bg-white dark:bg-zinc-900 rounded-md flex items-center justify-center border border-zinc-200 dark:border-zinc-700">
                                <span class="text-[10px] font-black text-green-600 uppercase">M-Pesa</span>
                            </div>
                        </div>
                    </label>

                    {{-- Card Option --}}
                    <label
                        class="group flex items-center justify-between p-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-start gap-4">
                            {{-- Standard Radio --}}
                            <div class="flex items-center h-5 mt-1">
                                <flux:radio value="card" />
                            </div>
                            <div>
                                <flux:label class="font-medium text-zinc-800 dark:text-white cursor-pointer">Credit or
                                    Debit Card</flux:label>
                                <flux:description>Visa, Mastercard, or Amex via Stripe.</flux:description>
                            </div>
                        </div>

                        {{-- Right Aligned Logo --}}
                        <div class="shrink-0 ml-4">
                            <div
                                class="w-12 h-12 bg-white dark:bg-zinc-900 rounded-md flex items-center justify-center border border-zinc-200 dark:border-zinc-700">
                                <flux:icon.credit-card class="size-6 text-zinc-600 dark:text-zinc-400" />
                            </div>
                        </div>
                    </label>

                </flux:radio.group>

                {{-- Contextual Info Box --}}
                <div
                    class="m-4 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @if ($paymentMethod === 'mpesa')
                        <div class="flex gap-2">
                            <flux:icon.information-circle class="size-4 text-green-600 mt-0.5" />
                            <flux:text size="sm">Enter your PIN on the STK push within 60 seconds.</flux:text>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <flux:icon.lock-closed class="size-4 text-blue-600 mt-0.5" />
                            <flux:text size="sm">Securely processed. We never store your card data.</flux:text>
                        </div>
                    @endif
                </div>
            @else
                {{-- Fallback for Single Gateway --}}
                <div class="p-4 flex items-center justify-between">
                    <flux:text class="font-medium capitalize">{{ $this->activeGateway }}</flux:text>
                    <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                </div>
            @endif

            {{-- Footer Action --}}
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-end bg-zinc-50/30">
                <flux:button wire:click="confirm" variant="primary" class="cursor-pointer">
                    Confirm Payment Method
                </flux:button>
            </div>
        </div>
    </flux:card>

    <flux:link :href="route('products')" wire:navigate class="text-xs">
        ← Continue shopping
    </flux:link>
</div>
