<?php

use App\Services\{OrderSummaryService, CartService, CheckoutSession, CheckoutService, QuotationService};
use App\Services\Payment\ValueObjects\PaymentResponse;
use Livewire\Attributes\{Computed, On};
use Livewire\Component;
use App\Models\{Order, Address};
use App\Enums\{OrdersStatus, PaymentStatus};

new class extends Component {
    public bool $isProcessing = false;

    // =====================================
    // COMPUTED PROPERTIES
    // =====================================

    // Summary totals (subtotal, discount, shipping, total) from the cart
    // Refreshed via the shipping-updated event when the customer changes their shipping method on the shipping step.
    #[Computed]
    public function summary(): array
    {
        return app(OrderSummaryService::class)->summary();
    }

    // Live cart items with their product relationships for the item list and the requires_quotation badge in the summary card.
    #[Computed]
    public function cartItems()
    {
        return app(CartService::class)->getCart()->items()->with('product')->get();
    }

    // =========================================
    // MAIN ENTRY POINT
    //
    // Determines which of the three checkout paths to follow and delegates to the appropriate handler. Detection order is intentional:
    //
    // Path C is checked FIRST - a requires_quotation product in the cart overrides everything, even if the shipping zone is covered.
    //
    // Path B is checked SECOND - out-of-zone delivery (method_type=quote).
    //
    // Path A is the fallback - standard sales order with payment.
    // ========================================

    public function completeOrder()
    {
        $this->isProcessing = true;

        try {
            $shippingType = app(CheckoutSession::class)->getShipping()['method_type'] ?? '';

            // Path C - product quotation
            // Cart contains at least one requires_quotation product.
            // The entire order becomes a quotation regardless of shipping zone.
            if ($this->cartHasQuotationProducts()) {
                return $this->processQuoteRequest('product');
            }

            // Path B - delivery quotation
            // All products are standard but the customer's address is outside the covered delivery zone and they chose delivery (not pickup).
            if ($shippingType === 'quote') {
                return $this->processQuoteRequest('delivery');
            }

            // Path A - normal sales orders
            // Standard products + covered zone (or pickup). Full payment flow.
            $response = app(CheckoutService::class)->initiateCheckout();

            return $this->handlePaymentResponse($response);
        } catch (\Exception $e) {
            $this->isProcessing = false;
            $this->handleCheckoutError($e);

            Log::error('Checkout Failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }

        return null;
    }

    // ==========================================
    // PATH A - Payment response handler
    //
    // Handles the three possible outcomes from the payment gateway:
    // - Failed -> show error notification, stay on page
    // - Redirect -> send customer to gateway hosted page (Pesawise / Stripe)
    // - STK push -> dispatch event to show the M-pesa waiting modal
    // ==========================================

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

    // ==========================================
    // PATH b + PATH C - Quote request handler
    //
    // $quotationType: 'delivery' (Path B) | 'product' (Path C)
    //
    // Key differences from the normal sales order flow (Path A):
    // - document_type = 'quotation'
    // - quotation_type = $quotationType
    // - status = PENDING_QUOTE (note PENDING)
    // - reference = QTN-2026-000001 (not SO-)
    // - shipping_cents = 0 -> admin prices this when sending the quote
    // - total_cents = subtotal only (shipping not yet confirmed)
    // - Stock is NOT decremented (order is not confirmed yet)
    // - No Payment record created
    // - No gateway called
    // - Cart is cleared after the quotation is created
    // - Customer is redirected to the quote-success confirmation page
    // ==========================================
    private function processQuoteRequest(string $quotationType)
    {
        $session = app(CheckoutSession::class);
        $cart = app(CartService::class);
        $shipping = $session->getShipping();

        // Resolve the address from the session
        $addressId = $session->getAddressId();
        $address = Address::with(['county', 'area', 'shippingZone'])->find($addressId);

        // Guard: if the session has expired the customer needs to restart
        if (!$address || !$shipping) {
            $this->isProcessing = false;
            $this->dispatch('notify', variant: 'danger', message: 'Session expired. Please start checkout again.');
            return $this->redirectRoute('checkout.shipping', navigate: true);
        }

        $cartInstance = $cart->getCart();
        $cartItems = $cartInstance->items()->with('product.brand')->get();
        $cartSummary = $cart->summary($cartInstance);

        // Money as integer cents — same pattern as CheckoutService
        $subtotalCents = (int) round($cartSummary['subtotal'] * 100);
        $discountCents = (int) round($cartSummary['discount'] * 100);

        //  DB transaction
        //
        // Quotation creation and item creation run atomically.
        // Stock is intentionally NOT decremented here — the order is not
        // confirmed until the customer accepts the quote and payment succeeds.

        $order = DB::transaction(function () use ($address, $cartItems, $cart, $cartInstance, $subtotalCents, $discountCents, $shipping, $quotationType) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'reference' => Order::generateReference('quotation'), // e.g. QTN-2026-000001
                'document_type' => 'quotation',
                'quotation_type' => $quotationType, // 'delivery' or 'product'
                'status' => OrdersStatus::PENDING_QUOTE,
                'payment_status' => PaymentStatus::PENDING,
                'currency' => 'KES',
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'shipping_cents' => 0, // TBD — admin sets when sending quote
                'tax_cents' => 0,
                'total_cents' => max(0, $subtotalCents - $discountCents), // shipping excluded
                'shipping_address' => [
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'full_name' => $address->full_name,
                    'phone_number' => $address->phone_number,
                    'address' => $address->address,
                    'area' => $address->area?->name,
                    'county' => $address->county?->name,
                    'zone' => $address->shippingZone?->name,
                ],
                'billing_address' => [
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'full_name' => $address->full_name,
                    'phone_number' => $address->phone_number,
                    'address' => $address->address,
                    'area' => $address->area?->name,
                    'county' => $address->county?->name,
                    'zone' => $address->shippingZone?->name,
                ],

                // Snapshot the shipping selection even though cost is TBD.
                // The zone_id and cost_breakdown tell admin where the customer is and what method they wanted — useful context for pricing.
                'shipping_snapshot' => [
                    'method_id' => $shipping['method_id'] ?? 0,
                    'method_name' => $shipping['method_name'],
                    'method_code' => $shipping['method_code'],
                    'method_type' => $shipping['method_type'],
                    'zone_id' => $shipping['zone_id'],
                    'rate_id' => null,
                    'station_id' => null,
                    'station_name' => null,
                    'cost' => 0,
                    'cost_breakdown' => $shipping['cost_breakdown'] ?? null,
                    'delivery_window' => null,
                    'weight_kg' => $cart->getWeight($cartInstance),
                ],

                // expires_at is null — admin sets this when sending the quote.
                // quoted_at is null — set when transitionTo(QUOTE_SENT) fires.
                'expires_at' => null,
                'quoted_at' => null,
            ]);

            // Seed the status history with a note that explains why the
            // quotation was created — useful for admin context.
            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrdersStatus::PENDING_QUOTE->value,
                'changed_by_user_id' => auth()->id(),
                'changed_by_type' => 'user',
                'notes' => match ($quotationType) {
                    'delivery' => 'Delivery quote requested — address outside covered delivery zone.',
                    'product' => 'Product quote requested — cart contains quotation-only items.',
                    default => 'Quote request submitted by customer.',
                },
            ]);

            // Create order items — same structure as a sales order.
            // requires_quotation is stored in the snapshot so the admin panel can show which items drove the quotation path.
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => (int) round($item->product->final_price * 100),
                    'unit_tax_cents' => 0,
                    'discount_cents' => (int) round(($item->product->price - $item->product->final_price) * 100 * $item->quantity),
                    'total_cents' => (int) round($item->product->final_price * 100 * $item->quantity),
                    'product_snapshot' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'slug' => $item->product->slug,
                        'image_path' => $item->product->image_path,
                        'price' => $item->product->price,
                        'sale_price' => $item->product->sale_price,
                        'final_price' => $item->product->final_price,
                        'weight_kg' => $item->product->weight ?? 0.5,
                        'brand' => $item->product->brand?->name,
                        'requires_quotation' => $item->product->requires_quotation,
                    ],
                ]);
            }

            return $order;
        });

        app(QuotationService::class)->notifyRequested($order);

        // Clear the cart and session now the quotation is safely stored
        $cart->clear();
        $session->clear();
        $this->isProcessing = false;

        // Send the customer to the quote confirmation page.
        // No payment is taken — they'll be contacted by the team.
        return $this->redirectRoute('checkout.quote-success', parameters: ['reference' => $order->reference], navigate: true);
    }

    // ===============================================
    // Cart quotation product detection
    //
    // Returns true if at least one item in the current cart belongs to a product with requires_quotation = true. Uses a database Exists check rather than loading all items into memory.
    //
    // Called in completeOrder() BEFORE the shipping type check so that Path C always takes priority over PATH B.
    // ===============================================
    private function cartHasQuotationProducts(): bool
    {
        return app(CartService::class)->getCart()->items()->whereHas('product', fn($q) => $q->where('requires_quotation', true))->exists();
    }

    // ==============================================
    // Error handler
    //
    // Maps raw exception messages to user-friendly notifcations
    // Specific messages (stock, shipping) are shown as - is since ther already contain useful context (e.g. product name + quantity).
    // Everything else gets a generic fallback to avoid exposing internals
    // ==============================================

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

    // ================================================
    // Events
    // ================================================

    // Fired by the shipping component when the customer changes their method.
    // Clears the computed summary so it recalculates with the new shipping cost.
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
                    @if ($item->product->requires_quotation)
                        <span
                            class="inline-block text-[10px] font-medium bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded mt-0.5">
                            Quote required
                        </span>
                    @endif
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
            @elseif ($this->summary['shipping_method_type'] === 'quote')
                <span class="text-amber-500 font-medium">TBD</span>
            @elseif ($this->summary['shipping_cost'] == 0)
                <span class="text-green-600 font-medium">Free</span>
            @else
                <span>{{ format_currency($this->summary['shipping_cost']) }}</span>
            @endif
        </div>

        <div class="flex justify-between font-semibold text-sm border-t pt-2 mt-1">
            <span>Total</span>
            {{-- When shipping is TBD, show a note instead of a misleading total --}}
            @if ($this->summary['shipping_method_type'] === 'quote')
                <span class="text-zinc-400 text-xs font-normal italic self-center">Shipping to be confirmed</span>
            @else
                <span>{{ format_currency($this->summary['total']) }}</span>
            @endif
        </div>
    </div>

    {{-- Place order / Send quote request button --}}
    <div class="p-3 border-t">
        @php
            // Button label is "Send Quote Request" for both Path B and Path C.
            // Path B: shipping method_type is 'quote' (out-of-zone delivery).
            // Path C: cart contains at least one requires_quotation product.
            $isQuote =
                $this->summary['shipping_method_type'] === 'quote' ||
                $this->cartItems->contains(fn($i) => $i->product->requires_quotation);
        @endphp

        <flux:button wire:click="completeOrder" wire:loading.attr="disabled" wire:target="completeOrder"
            class="w-full group cursor-pointer" variant="primary"
            :disabled="!$this->summary['shipping_selected'] || $isProcessing">
            {{ $isQuote ? 'Send Quote Request' : 'Place Order' }}
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

    {{-- M-Pesa STK push waiting modal — Path A only --}}
    <flux:modal name="stk-waiting" class="max-w-sm">
        <div x-data="{
            timeLeft: 60,
            checkoutRequestId: null,
            interval: null,
        
            init() {
                Livewire.on('stk-push-initiated', ({
                    checkoutRequestId
                }) => {
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
