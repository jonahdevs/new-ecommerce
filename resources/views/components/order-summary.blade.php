<?php

// ============================================================
//  UPDATED order-summary.blade.php
//  Reads from OrderSummaryService which reads from session.
//  The completeOrder() method now creates a DeliveryOrder.
// ============================================================

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Services\CheckoutSession;
use App\Services\OrderSummaryService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public ?string $errorType = null;

    #[Computed]
    public function summary(): array
    {
        return app(OrderSummaryService::class)->summary();
    }

    public function completeOrder(): mixed
    {
        $this->errorMessage = null;
        $this->errorType = null;
        $this->isProcessing = true;

        try {
            return app(\App\Services\CheckoutService::class)->initiateCheckout();
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->handleCheckoutError($e);

            logger()->error('Checkout initiation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
            ]);
        }

        return null;
    }

    private function handleCheckoutError(\Exception $e): void
    {
        $message = $e->getMessage();

        [$this->errorType, $this->errorMessage] = match (true) {
            str_contains($message, 'already in progress') => ['processing', 'Your checkout is being processed. Please wait...'],
            str_contains($message, 'out of stock') => ['inventory', $message],
            str_contains($message, 'shipping not selected') => ['shipping', 'Please select a shipping method to continue.'],
            str_contains($message, 'shipping address') => ['address', 'Please add a shipping address to continue.'],
            str_contains($message, 'cart is empty') => ['empty-cart', 'Your cart is empty.'],
            str_contains($message, 'minimum order value') => ['min-order', $message],
            str_contains($message, 'payment') => ['gateway', 'Unable to connect to payment service. Please try again.'],
            default => ['general', 'Something went wrong. Please try again or contact support.'],
        };

        $this->dispatch('notify', message: $this->errorMessage, variant: 'danger');
    }

    public function clearError(): void
    {
        $this->errorMessage = null;
        $this->errorType = null;
    }
};

?>

<flux:card class="p-0 sticky top-44">
    <div class="px-3 py-2 border-b">
        <flux:heading>Order Summary</flux:heading>
    </div>

    <div class="p-4 flex flex-col gap-3">
        {{-- Subtotal --}}
        <div class="flex items-center justify-between">
            <flux:text class="flex items-center gap-1">
                <flux:icon.receipt class="size-4" />
                Subtotal
            </flux:text>
            <flux:heading>{{ format_currency($this->summary['subtotal']) }}</flux:heading>
        </div>

        {{-- Discount --}}
        @if ($this->summary['discount'] > 0)
            <div class="flex items-center justify-between">
                <flux:text class="flex items-center gap-1 text-green-600">
                    <flux:icon.badge-percent class="size-4" />
                    Discount
                </flux:text>
                <flux:heading class="text-green-600">
                    − {{ format_currency($this->summary['discount']) }}
                </flux:heading>
            </div>
        @endif

        {{-- Shipping --}}
        <div class="flex items-center justify-between">
            <flux:text class="flex items-center gap-1">
                <flux:icon.truck class="size-4" />
                Shipping
                @if ($this->summary['shipping_method'])
                    <span class="text-zinc-400 text-xs ml-1">({{ $this->summary['shipping_method'] }})</span>
                @endif
            </flux:text>

            @if (!$this->summary['shipping_selected'])
                <flux:link :href="route('checkout.shipping')" wire:navigate class="text-xs text-orange-500">
                    Select method
                </flux:link>
            @elseif ($this->summary['shipping_cost'] === 0.0)
                <flux:heading class="text-green-600">Free</flux:heading>
            @else
                <flux:heading>{{ format_currency($this->summary['shipping_cost']) }}</flux:heading>
            @endif
        </div>

        {{-- Station name for PUS --}}
        @if ($this->summary['station_name'])
            <div class="text-xs text-zinc-400 -mt-2 pl-5">
                Pickup at: {{ $this->summary['station_name'] }}
            </div>
        @endif

        {{-- Delivery window --}}
        @if ($this->summary['shipping_window'])
            <div class="text-xs text-zinc-400 -mt-2 pl-5">
                Est. {{ $this->summary['shipping_window'] }}
            </div>
        @endif
    </div>

    {{-- Total --}}
    <div class="flex items-center justify-between border-t px-4 py-3">
        <flux:text class="font-semibold">Total</flux:text>
        <flux:heading class="font-semibold text-lg">
            {{ format_currency($this->summary['total']) }}
        </flux:heading>
    </div>

    {{-- Error message --}}
    @if ($this->errorMessage)
        <div class="px-4 pb-3">
            <div class="bg-red-50 border border-red-200 rounded-md px-3 py-2 text-xs text-red-700">
                {{ $this->errorMessage }}
            </div>
        </div>
    @endif

    {{-- Place order --}}
    <div class="p-3 border-t">
        <flux:button wire:click="completeOrder" class="w-full group cursor-pointer" variant="primary"
            :disabled="! $this->summary['shipping_selected'] || $isProcessing">
            {{ $isProcessing ? 'Processing...' : 'Place Order' }}
            <x-slot name="iconTrailing">
                <flux:icon.chevron-right class="size-4 ms-3 group-hover:translate-x-1 transition-transform" />
            </x-slot>
        </flux:button>

        @if (!$this->summary['shipping_selected'])
            <p class="text-xs text-center text-orange-500 mt-2">
                Select a shipping method to place your order
            </p>
        @endif

        <div class="mt-2 flex items-center justify-center gap-1 text-xs text-zinc-400">
            <flux:icon.lock-closed class="size-3" />
            <span>Secure checkout powered by Pesawise</span>
        </div>
    </div>
</flux:card>
