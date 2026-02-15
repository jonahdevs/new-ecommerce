<?php

use App\Services\CheckoutService;
use App\Services\OrderSummaryService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $isProcessing = false;
    public ?string $errorMessage = null;
    public ?string $errorType = null;

    #[Computed]
    public function summary()
    {
        return app(OrderSummaryService::class)->summary();
    }

    public function proceedToCheckout()
    {
        // Reset state
        $this->errorMessage = null;
        $this->errorType = null;
        $this->isProcessing = true;

        try {
            // Attempt checkout
            return app(CheckoutService::class)->initiateCheckout();
        } catch (\Exception $e) {
            $this->isProcessing = false;

            // Categorize and format error for user
            $this->handleCheckoutError($e);

            // Log for debugging
            logger()->error('Checkout initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
            ]);

            \Log::error('Checkout initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
            ]);
        }
    }

    /**
     * Handle checkout errors and provide user-friendly messages
     */
    private function handleCheckoutError(\Exception $e): void
    {
        $message = $e->getMessage();

        // Categorize error and set appropriate type
        if (str_contains($message, 'already in progress')) {
            $this->errorType = 'processing';
            $this->errorMessage = 'Your checkout is being processed. Please wait...';
        } elseif (str_contains($message, 'out of stock') || str_contains($message, 'became unavailable')) {
            $this->errorType = 'inventory';
            $this->errorMessage = $message; // Already user-friendly from service
            $this->dispatch('refresh-cart'); // Trigger cart refresh
        } elseif (str_contains($message, 'shipping address')) {
            $this->errorType = 'address';
            $this->errorMessage = 'Please add a shipping address to continue.';
            $this->dispatch('open-address-modal'); // Open address form
        } elseif (str_contains($message, 'cart is empty')) {
            $this->errorType = 'empty-cart';
            $this->errorMessage = 'Your cart is empty.';
        } elseif (str_contains($message, 'minimum order value')) {
            $this->errorType = 'minimum-order';
            $this->errorMessage = $message;
        } elseif (str_contains($message, 'payment gateway') || str_contains($message, 'payment service')) {
            $this->errorType = 'gateway';
            $this->errorMessage = 'Unable to connect to payment service. Please try again in a moment.';
        } else {
            // Generic error
            $this->errorType = 'general';
            $this->errorMessage = 'An error occurred during checkout. Please try again or contact support if the issue persists.';
        }

        // Dispatch notification
        $this->dispatch('notify', message: $this->errorMessage, type: 'error');
    }

    /**
     * Clear error and allow retry
     */
    public function clearError()
    {
        $this->errorMessage = null;
        $this->errorType = null;
    }
};

?>

<div class="border bg-white rounded-sm sticky top-44">
    <div class="px-3 py-2 border-b">
        <flux:heading>Order Summary</flux:heading>
    </div>

    {{-- Error Alert --}}
    @if ($errorMessage)
        <div class="p-3 border-b" x-data="{ show: true }" x-show="show" x-transition>
            <div
                class="flex items-start gap-2 p-3 rounded-lg
                {{ $errorType === 'inventory' ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200' }}">
                <flux:icon.exclamation-circle
                    class="size-5 {{ $errorType === 'inventory' ? 'text-orange-600' : 'text-red-600' }} flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <flux:text class="{{ $errorType === 'inventory' ? 'text-orange-700' : 'text-red-700' }} text-sm">
                        {{ $errorMessage }}
                    </flux:text>

                    @if ($errorType === 'address')
                        <flux:button size="sm" variant="ghost" class="mt-2"
                            wire:click="$dispatch('open-address-modal')">
                            Add Address
                        </flux:button>
                    @endif
                </div>
                <button @click="show = false; $wire.clearError()" class="text-gray-400 hover:text-gray-600">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>
    @endif

    {{-- Order Summary Details --}}
    <div class="p-5 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <flux:text>
                <flux:icon.receipt class="text-inherit size-4 inline-block me-1" />
                Subtotal
            </flux:text>
            <flux:heading>{{ format_currency($this->summary['subtotal']) }}</flux:heading>
        </div>

        <div class="flex items-center justify-between">
            <flux:text>
                <flux:icon.badge-percent class="text-inherit size-4 inline-block me-1" />
                Discount
            </flux:text>
            <flux:heading>{{ format_currency($this->summary['discount']) }}</flux:heading>
        </div>

        <div class="flex items-center justify-between">
            <flux:text>
                <flux:icon.truck class="text-inherit size-4 inline-block me-1" />
                Shipping
            </flux:text>
            <flux:heading>{{ format_currency($this->summary['shipping_cost']) }}</flux:heading>
        </div>
    </div>

    {{-- Total --}}
    <div class="flex items-center justify-between border-t px-3 py-2">
        <flux:text class="font-semibold text-base">Total</flux:text>
        <flux:heading class="font-semibold text-base">
            {{ format_currency($this->summary['total']) }}
        </flux:heading>
    </div>

    {{-- Checkout Button --}}
    <div class="p-3 border-t">
        <flux:button wire:click="proceedToCheckout" class="w-full group cursor-pointer relative" variant="primary"
            :disabled="$isProcessing">
            {{-- Button Content --}}
            <span wire:loading.remove wire:target="proceedToCheckout">
                Proceed to Checkout
            </span>
            <span wire:loading wire:target="proceedToCheckout" class="flex items-center gap-2">
                <svg class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Processing...
            </span>

            {{-- Icon --}}
            <x-slot name="iconTrailing">
                <flux:icon.chevron-right class="size-4 ms-3 group-hover:translate-x-1 transition-transform"
                    wire:loading.remove wire:target="proceedToCheckout" />
            </x-slot>
        </flux:button>

        {{-- Security Badge --}}
        <div class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-500">
            <flux:icon.lock-closed class="size-3" />
            <span>Secure checkout powered by Pesawise</span>
        </div>
    </div>
</div>

{{-- Alpine.js for additional interactivity --}}
@script
    <script>
        // Prevent double-click submission
        let checkoutInProgress = false;

        $wire.on('checkout-started', () => {
            checkoutInProgress = true;
        });

        $wire.on('checkout-completed', () => {
            checkoutInProgress = false;
        });

        // Listen for cart updates
        $wire.on('refresh-cart', () => {
            // Trigger cart component refresh
            window.dispatchEvent(new CustomEvent('cart-updated'));
        });
    </script>
@endscript
