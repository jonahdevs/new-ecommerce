<?php

use App\Services\{OrderSummaryService, CartService, CheckoutSession, CheckoutService};
use App\Services\Payment\ValueObjects\PaymentResponse;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;
use App\Models\{Order, Address};
use App\Enums\{OrderStatus, PaymentStatus};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public bool $isProcessing = false;

    // =====================================================
    // Computed properties
    // =====================================================

    #[Computed]
    public function summary(): array
    {
        return app(OrderSummaryService::class)->summary();
    }

    #[Computed]
    public function cartItems()
    {
        return app(CartService::class)->getCart()->items()->with('product')->get();
    }

    // =====================================================
    // Main entry point
    //
    // Single path — standard cart checkout only.
    // Quote products can no longer enter the cart, so no
    // path detection is needed here. The quote flow is
    // handled entirely by a separate quote-basket component.
    // =====================================================

    public function completeOrder()
    {
        $this->isProcessing = true;

        try {
            $response = app(CheckoutService::class)->initiateCheckout();
            return $this->handlePaymentResponse($response);
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->handleCheckoutError($e);

            Log::error('Checkout failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }

        return null;
    }

    // =====================================================
    // Payment response handler
    // =====================================================

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
            default => null,
        };
    }

    // =====================================================
    // Error handler
    // =====================================================

    private function handleCheckoutError(\Exception $e): void
    {
        $message = $e->getMessage();

        $errorMessage = match (true) {
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

    // =====================================================
    // Cart actions
    // =====================================================

    public function removeItem(int $cartItemId): void
    {
        try {
            app(CartService::class)->removeItem($cartItemId);
            unset($this->cartItems);
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->dispatch('notify', variant: 'danger', message: 'Could not remove item. Please try again.');
        }
    }

    // =====================================================
    // Events
    // =====================================================

    #[On('shipping-updated')]
    public function refreshSummary(): void
    {
        unset($this->summary);
    }
};
?>

<flux:card class="p-0">

    {{-- Header --}}
    <div class="px-4 py-2.5 border-b">
        <flux:heading>Order Summary</flux:heading>
    </div>

    {{-- Items list --}}
    <div class="divide-y max-h-52 overflow-y-auto">
        @foreach ($this->cartItems as $item)
            <div class="flex items-center gap-2.5 px-4 py-3">
                <div class="w-10 h-10 rounded border bg-zinc-50 overflow-hidden shrink-0">
                    @if ($item->product?->image_path)
                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"
                            class="w-full h-full object-cover" />
                    @else
                        <flux:icon.photo class="w-full h-full p-1.5 text-zinc-300" />
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium truncate">{{ $item->product->name }}</p>
                    <p class="text-xs text-zinc-400">× {{ $item->quantity }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-semibold">
                        {{ format_currency($item->product->final_price * $item->quantity) }}
                    </span>
                    <button wire:click="removeItem({{ $item->id }})" wire:confirm="Remove this item from your cart?"
                        class="text-zinc-300 hover:text-red-500 transition-colors cursor-pointer" title="Remove item">
                        <flux:icon.x-mark class="size-3.5" />
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Totals --}}
    <div class="px-4 py-3 border-t space-y-1.5">

        <div class="flex justify-between text-sm text-zinc-500">
            <span class="flex items-center gap-1.5">
                <flux:icon.receipt class="size-3.5 shrink-0" />
                Subtotal
            </span>
            <span>{{ format_currency($this->summary['subtotal']) }}</span>
        </div>

        @if ($this->summary['discount'] > 0)
            <div class="flex justify-between text-sm text-green-600">
                <span class="flex items-center gap-1.5">
                    <flux:icon.badge-percent class="size-3.5 shrink-0" />
                    Discount
                </span>
                <span>− {{ format_currency($this->summary['discount']) }}</span>
            </div>
        @endif

        <div class="flex justify-between text-sm text-zinc-500">
            <span class="flex items-center gap-1.5">
                <flux:icon.truck class="size-3.5 shrink-0" />
                Shipping
            </span>
            @if (!$this->summary['shipping_selected'])
                <flux:link :href="route('checkout.shipping')" wire:navigate class="text-amber-500">
                    Select <flux:icon.arrow-long-right class="size-3.5 inline-block ms-0.5" />
                </flux:link>
            @elseif ($this->summary['shipping_cost'] == 0)
                <span class="text-green-600 font-medium">Free</span>
            @else
                <span>{{ format_currency($this->summary['shipping_cost']) }}</span>
            @endif
        </div>

        <div class="flex justify-between font-semibold text-sm border-t pt-2 mt-1">
            <span>Total</span>
            <span>{{ format_currency($this->summary['total']) }}</span>
        </div>
    </div>

    {{-- Place order button --}}
    <div class="p-3 border-t">
        <flux:button wire:click="completeOrder" wire:loading.attr="disabled" wire:target="completeOrder"
            class="w-full group cursor-pointer" variant="primary"
            :disabled="!$this->summary['shipping_selected'] || $isProcessing">
            Place Order
            <x-slot name="iconTrailing">
                <flux:icon.chevron-right class="size-4 ms-3 group-hover:translate-x-1 transition-transform"
                    wire:loading.class="hidden" wire:target="completeOrder" />
            </x-slot>
        </flux:button>

        <div class="mt-2 flex items-center justify-center gap-1 text-xs text-zinc-400">
            <flux:icon.lock-closed class="size-3" />
            <span>Secure checkout</span>
        </div>
    </div>

    {{-- M-Pesa STK push modal --}}
    <flux:modal name="stk-waiting" class="max-w-sm">
        <div x-data="{
            timeLeft: 60,
            checkoutRequestId: null,
            interval: null,
        
            init() {
                Livewire.on('stk-push-initiated', ({ checkoutRequestId }) => {
                    this.checkoutRequestId = checkoutRequestId;
                    $flux.modal('stk-waiting').show();
                    this.startCountdown();
                });
            },
        
            startCountdown() {
                if (this.interval) clearInterval(this.interval);
                this.timeLeft = 60;
                this.interval = setInterval(() => {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        clearInterval(this.interval);
                        window.location.href = '{{ route('customer.orders.index') }}';
                    }
                }, 1000);
            },
        }">
            <div class="text-center p-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <flux:icon.device-phone-mobile class="size-8 text-green-600" />
                </div>

                <flux:heading size="lg" class="mb-2">Check your phone</flux:heading>

                <flux:text class="text-zinc-500 text-sm mb-6">
                    An M-Pesa payment request has been sent to your phone.
                    Enter your PIN to complete payment.
                </flux:text>

                <div class="text-2xl font-mono font-bold text-zinc-800 mb-2" x-text="timeLeft + 's'"></div>
                <div class="w-full bg-zinc-100 rounded-full h-1.5 mb-6">
                    <div class="bg-green-500 h-1.5 rounded-full transition-all duration-1000"
                        :style="'width: ' + (timeLeft / 60 * 100) + '%'"></div>
                </div>

                <flux:text class="text-xs text-zinc-400">Waiting for confirmation...</flux:text>
            </div>
        </div>
    </flux:modal>

</flux:card>
