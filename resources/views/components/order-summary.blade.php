<?php

use App\Services\{OrderSummaryService, CartService, CheckoutSession, CheckoutService};
use App\Services\Payment\ValueObjects\PaymentResponse;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;
use App\Models\{Order, Address};
use App\Enums\{OrderStatus, PaymentStatus};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        return app(CartService::class)
            ->getCart()
            ->items()
            ->with(['product', 'variant' => fn($q) => $q->with(['attributeValues:id,attribute_id,value,label', 'attributeValues.attribute:id,name'])])
            ->get();
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
            $this->dispatch('notify', title: 'Payment Failed', variant: 'danger', message: $response->message ?? 'Payment initiation failed. Please try again.');
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

        $this->dispatch('notify', title: 'Checkout Failed', variant: 'danger', message: $errorMessage);
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
            $this->dispatch('notify', title: 'Remove Failed', variant: 'danger', message: 'Could not remove item. Please try again.');
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

    #[On('complete-order')]
    public function handleCompleteOrder(): void
    {
        $this->completeOrder();
    }
};
?>

<div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">

    {{-- Header --}}
    <div class="px-5 py-4 border-b border-zinc-200 bg-white">
        <h3 class="text-[13px] font-bold uppercase tracking-widest text-zinc-950">Order Summary</h3>
    </div>

    {{-- Items list --}}
    <div class="divide-y divide-zinc-200 max-h-52 overflow-y-auto">
        @foreach ($this->cartItems as $item)
            @php
                $variant = $item->variant;
                $imageUrl = $variant?->image_path ? Storage::url($variant->image_path) : $item->product?->image_url;
                $unitPrice = $variant?->final_price ?? $item->product->final_price;
                $variantAttrs = $variant
                    ? $variant->attributeValues->mapWithKeys(
                        fn($av) => [$av->attribute->name => $av->label ?: $av->value],
                    )
                    : collect();
            @endphp
            <div class="flex items-center gap-3 px-4 py-3.5">
                <div class="w-12 h-12 rounded border border-zinc-200 bg-zinc-50 overflow-hidden shrink-0">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}"
                            class="w-full h-full object-cover" />
                    @else
                        <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-zinc-950 truncate mb-0.5">{{ $item->product->name }}</p>
                    @if ($variantAttrs->isNotEmpty())
                        <p class="text-[11px] text-zinc-500 truncate font-medium mb-1">
                            {{ $variantAttrs->map(fn($v, $k) => "$k: $v")->join(' · ') }}
                        </p>
                    @endif
                    <p class="text-[11px] text-zinc-400 font-medium">Qty: {{ $item->quantity }}</p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-[14px] font-bold text-zinc-950">
                        {{ format_currency($unitPrice * $item->quantity) }}
                    </span>
                    <button wire:click="removeItem({{ $item->id }})" wire:confirm="Remove this item from your cart?"
                        class="text-zinc-300 hover:text-red-500 transition-colors cursor-pointer" title="Remove item">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Totals --}}
    <div class="px-5 py-4 bg-zinc-50 border-t border-zinc-200 space-y-3">

        <div class="flex justify-between items-center">
            <span class="text-[13px] text-zinc-500 font-medium">Subtotal</span>
            <span class="text-[14px] text-zinc-950 font-bold">{{ format_currency($this->summary['subtotal']) }}</span>
        </div>

        @if ($this->summary['discount'] > 0)
            <div class="flex justify-between items-center">
                <span class="text-[13px] text-green-600 font-medium">Discount</span>
                <span class="text-[14px] text-green-600 font-bold">−
                    {{ format_currency($this->summary['discount']) }}</span>
            </div>
        @endif

        <div class="flex justify-between items-center">
            <span class="text-[13px] text-zinc-500 font-medium">Shipping</span>
            @if (!$this->summary['shipping_selected'])
                <flux:link :href="route('checkout.shipping')" wire:navigate
                    class="text-primary text-[11px] font-bold uppercase tracking-wider">
                    Select <flux:icon.arrow-long-right class="size-3 inline-block ms-0.5" />
                </flux:link>
            @elseif ($this->summary['shipping_cost'] == 0)
                <span class="text-[14px] text-green-600 font-bold">FREE</span>
            @else
                <span
                    class="text-[14px] text-zinc-950 font-bold">{{ format_currency($this->summary['shipping_cost']) }}</span>
            @endif
        </div>

        @if ($this->summary['tax_enabled'] && !$this->summary['tax_inclusive'] && $this->summary['tax'] > 0)
            <div class="flex justify-between items-center">
                <span class="text-[13px] text-zinc-500 font-medium">
                    {{ $this->summary['tax_name'] }} ({{ $this->summary['tax_rate'] }})
                </span>
                <span class="text-[14px] text-zinc-950 font-bold">{{ format_currency($this->summary['tax']) }}</span>
            </div>
        @endif

        <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
            <span class="text-[14px] font-bold uppercase tracking-widest text-zinc-950">Total</span>
            <span class="text-[22px] font-black text-primary leading-none">
                {{ format_currency($this->summary['total']) }}
            </span>
        </div>
    </div>

    {{-- Place order button --}}
    <div class="p-4 border-t border-zinc-200 bg-white">
        @isset($slot)
            {{-- Custom button content from parent page --}}
            {{ $slot }}
        @else
            {{-- Default Place Order button --}}
            <flux:button wire:click="completeOrder" wire:loading.attr="disabled" wire:target="completeOrder"
                class="w-full group cursor-pointer" variant="customer-primary" size="customer-lg"
                :disabled="!$this->summary['shipping_selected'] || $isProcessing">
                <span wire:loading.remove wire:target="completeOrder">Place Order</span>
                <span wire:loading wire:target="completeOrder" class="flex items-center gap-2">
                    <flux:icon.arrow-path class="size-3.5 animate-spin" />
                    Processing...
                </span>
                <x-slot name="iconTrailing">
                    <flux:icon.chevron-right class="size-3.5 group-hover:translate-x-1 transition-transform"
                        wire:loading.class="hidden" wire:target="completeOrder" />
                </x-slot>
            </flux:button>

            <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-zinc-400 font-medium">
                <flux:icon.shield-check class="size-3" />
                <span class="uppercase tracking-widest">SSL Encrypted & Secure</span>
            </div>
        @endisset
    </div>

    {{-- We Accept & Trust --}}
    <div class="py-4 px-5 border-t border-zinc-100">
        <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-3">We accept</div>
        <div class="flex flex-wrap gap-1.5 mb-6">
            @foreach (['VISA', 'MPESA', 'MASTERCARD', 'PAYPAL'] as $pay)
                <span
                    class="inline-block px-2 py-1 bg-zinc-100 border border-zinc-200 rounded text-[9px] font-extrabold text-zinc-600 tracking-wider">{{ $pay }}</span>
            @endforeach
        </div>

        <div class="space-y-3">
            <div class="flex items-center gap-2.5 text-[12px] text-zinc-500 font-medium">
                <flux:icon.arrow-path class="size-4 text-zinc-400 shrink-0" />
                <span>30-Day Easy Returns Policy</span>
            </div>
            <div class="flex items-center gap-2.5 text-[12px] text-zinc-500 font-medium">
                <flux:icon.truck class="size-4 text-zinc-400 shrink-0" />
                <span>Free delivery on orders over KES 5,000</span>
            </div>
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
</div>
