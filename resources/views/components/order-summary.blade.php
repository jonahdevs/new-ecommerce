<?php

use App\Services\OrderSummaryService;
use App\Services\Payment\ValueObjects\PaymentResponse;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $isProcessing = false;

    //  Computed
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
            $response = app(\App\Services\CheckoutService::class)->initiateCheckout();

            return $this->handlePaymentResponse($response);
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->handleCheckoutError($e);
            logger()->error('Checkout failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }

        return null;
    }

    //  Response handler

    private function handlePaymentResponse(PaymentResponse $response): mixed
    {
        if ($response->isFailed()) {
            $this->isProcessing = false;

            $this->dispatch('notify', variant: 'danger', message: $response->message ?? 'Payment initiation failed. Please try again.');
            return null;
        }

        return match (true) {
            $response->isRedirect() => redirect()->away($response->url),
            $response->isStkPush() => $this->dispatch('stk-push-initiated', checkoutRequestId: $response->checkoutRequestId),
            $response->isFailed() => null, // handled above
            default => null,
        };
    }

    //  Error handler

    private function handleCheckoutError(\Exception $e): void
    {
        $message = $e->getMessage();

        $errorMessage = match (
            true // ← local variable, not $this->
        ) {
            str_contains($message, 'already in progress') => 'Your checkout is being processed. Please wait...',
            str_contains($message, 'out of stock'), str_contains($message, 'units available') => $message,
            str_contains($message, 'shipping not selected') => 'Please select a shipping method to continue.',
            str_contains($message, 'shipping address') => 'Please add a shipping address to continue.',
            str_contains($message, 'cart is empty') => 'Your cart is empty.',
            str_contains($message, 'minimum order') => $message,
            str_contains($message, 'payment') => 'Unable to connect to payment service. Please try again.',
            default => 'Something went wrong. Please try again.',
        };

        $this->dispatch('notify', variant: 'danger', message: $errorMessage);
    }
};

?>

<flux:card class="p-0 sticky top-44">

    {{-- Header --}}
    <div class="px-4 py-2.5 border-b">
        <flux:heading>Order Summary</flux:heading>
    </div>

    {{-- Line items --}}
    <div class="p-4 flex flex-col gap-3">

        {{-- Subtotal --}}
        <div class="flex items-center justify-between">
            <flux:text class="flex items-center gap-1.5">
                <flux:icon.receipt class="size-4 shrink-0" />
                Subtotal
            </flux:text>
            <flux:heading>{{ format_currency($this->summary['subtotal']) }}</flux:heading>
        </div>

        {{-- Discount — only show if non-zero --}}
        @if ($this->summary['discount'] > 0)
            <div class="flex items-center justify-between">
                <flux:text class="flex items-center gap-1.5 text-green-600">
                    <flux:icon.badge-percent class="size-4 shrink-0" />
                    Discount
                </flux:text>
                <flux:heading class="text-green-600">
                    − {{ format_currency($this->summary['discount']) }}
                </flux:heading>
            </div>
        @endif

        {{-- Shipping --}}
        <div class="flex items-center justify-between">
            <flux:text class="flex items-center gap-1.5">
                <flux:icon.truck class="size-4 shrink-0" />
                <span>
                    Shipping
                    @if ($this->summary['shipping_method'])
                        <span class="text-zinc-400 text-xs">· {{ $this->summary['shipping_method'] }}</span>
                    @endif
                </span>
            </flux:text>

            @if (!$this->summary['shipping_selected'])
                <flux:link :href="route('checkout.shipping')" wire:navigate class="text-xs text-amber-500">
                    Select
                    <flux:icon.arrow-long-right class="size-4 inline-block ms-1" />
                </flux:link>
            @elseif ($this->summary['shipping_cost'] == 0)
                <flux:heading class="text-green-600">Free</flux:heading>
            @else
                <flux:heading>{{ format_currency($this->summary['shipping_cost']) }}</flux:heading>
            @endif
        </div>

        {{-- PUS station --}}
        @if ($this->summary['station_name'])
            <flux:text class="text-xs text-zinc-400 -mt-2 pl-6">
                Pickup: {{ $this->summary['station_name'] }}
            </flux:text>
        @endif

        {{-- Delivery window --}}
        @if ($this->summary['shipping_window'])
            <flux:text class="text-xs text-zinc-400 -mt-2 pl-6">
                Est. {{ $this->summary['shipping_window'] }}
            </flux:text>
        @endif

    </div>

    {{-- Total --}}
    <div class="flex items-center justify-between border-t px-4 py-3">
        <flux:text class="font-semibold text-base">Total</flux:text>
        <flux:heading class="font-semibold text-lg">
            {{ format_currency($this->summary['total']) }}
        </flux:heading>
    </div>


    {{-- Place order button --}}
    <div class="p-3 border-t">
        <flux:button wire:click="completeOrder" wire:loading.attr="disabled" class="w-full group cursor-pointer"
            variant="primary" :disabled="! $this->summary['shipping_selected'] || $isProcessing">
            {{ $isProcessing ? 'Processing...' : 'Place Order' }}
            <x-slot name="iconTrailing">
                <flux:icon.chevron-right class="size-4 ms-3 group-hover:translate-x-1 transition-transform"
                    wire:loading.class="hidden" wire:target="completeOrder" />
            </x-slot>
        </flux:button>

        @if (!$this->summary['shipping_selected'])
            <p class="text-xs text-center text-amber-500 mt-2">
                Select a shipping method to continue
            </p>
        @endif

        <div class="mt-2 flex items-center justify-center gap-1 text-xs text-zinc-400">
            <flux:icon.lock-closed class="size-3" />
            <span>Secure checkout</span>
        </div>
    </div>


    {{-- M-Pesa STK waiting screen --}}
    <flux:modal name="stk-waiting" class="max-w-sm">
        <div x-data="stkWaiting()" x-on:stk-push-initiated.window="start($event.detail.checkoutRequestId)"
            x-init="init()">
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <flux:icon.device-phone-mobile class="size-8 text-green-600" />
                </div>

                <flux:heading size="lg" class="mb-2">Check your phone</flux:heading>

                <flux:text class="text-zinc-500 text-sm mb-6">
                    An M-Pesa payment request has been sent to your phone.
                    Enter your PIN to complete payment.
                </flux:text>

                {{-- Countdown --}}
                <div class="text-2xl font-mono font-bold text-zinc-800 mb-2" x-text="timeLeft + 's'"></div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5 mb-6">
                    <div class="bg-green-500 h-1.5 rounded-full transition-all duration-1000"
                        x-bind:style="'width: ' + (timeLeft / 60 * 100) + '%'"></div>
                </div>

                <flux:text class="text-xs text-zinc-400">
                    Waiting for confirmation...
                </flux:text>
            </div>
        </div>
    </flux:modal>
</flux:card>



@script
    <script>
        function stkWaiting() {
            return {
                timeLeft: 60,
                checkoutRequestId: null,
                interval: null,

                init() {
                    // Listen for Livewire event to open modal
                    Livewire.on('stk-push-initiated', ({
                        checkoutRequestId
                    }) => {
                        this.checkoutRequestId = checkoutRequestId;
                        $flux.modal('stk-waiting').show();
                        this.startCountdown();
                    });
                },

                start(id) {
                    this.checkoutRequestId = id;
                    this.startCountdown();
                },

                startCountdown() {
                    this.timeLeft = 60;
                    this.interval = setInterval(() => {
                        this.timeLeft--;
                        if (this.timeLeft <= 0) {
                            clearInterval(this.interval);
                            // Redirect to order status page — let webhook update the order
                            window.location.href = '{{ route('customer.orders.index') }}';
                        }
                    }, 1000);
                },
            };
        }
    </script>
@endscript
