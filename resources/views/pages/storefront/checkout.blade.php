<?php

use App\Enums\OrderStatus;
use App\Jobs\ResolveAddressCounty;
use App\Livewire\Concerns\InteractsWithAddressBook;
use App\Livewire\Concerns\InteractsWithPaystack;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\DeliveryQuoteResult;
use App\Services\DeliveryResolver;
use App\Support\StorefrontSession;
use App\Support\TaxCalculator;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Checkout')] class extends Component {
    use InteractsWithAddressBook, InteractsWithPaystack;

    public string $deliveryMethod = 'delivery';

    /** The order placed in this checkout session, awaiting Paystack payment. */
    public ?int $payOrderId = null;

    // ==================================================
    // COUPON
    // ==================================================

    public string $couponInput = '';
    public ?int $appliedCouponId = null;
    public string $appliedCouponCode = '';
    public int $discountCents = 0;

    // ==================================================
    // ADDRESS FORM MODALS
    // ==================================================
    // Address modal state + persistence lives in InteractsWithAddressBook.
    public bool $showDeliveryModal = false;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        if (StorefrontSession::cartLines()->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $default = auth()->user()->addresses()->orderByDesc('is_default')->first();
        $this->selectedAddressId = $default?->id;
    }

    #[Computed]
    public function lines(): Collection
    {
        return StorefrontSession::cartLines();
    }

    /**
     * Serviceability + price for the current delivery choice. Pickup is always
     * free; delivery is resolved from the selected address pin through the
     * zone + promotion matrix.
     */
    #[Computed]
    public function deliveryQuote(): DeliveryQuoteResult
    {
        if ($this->deliveryMethod === 'pickup') {
            return new DeliveryQuoteResult(serviceable: true, feeCents: 0, isFree: true);
        }

        $address = $this->selectedAddress;
        $subtotalCents = (int) $this->lines->sum('line_total_cents');

        return app(DeliveryResolver::class)->quoteDefault($address?->latitude, $address?->longitude, $subtotalCents);
    }

    /**
     * Recompute the delivery quote whenever the chosen address changes.
     */
    protected function afterAddressSelected(): void
    {
        unset($this->deliveryQuote);
    }

    /**
     * After saving a new address, refresh the delivery quote and resolve the
     * county from the pin (off-request) for the sales-by-county report.
     */
    protected function afterAddressSaved(Address $address): void
    {
        unset($this->deliveryQuote);

        if ($address->latitude !== null) {
            ResolveAddressCounty::dispatch($address->id);
        }
    }

    public function openDeliveryModal(): void
    {
        $this->showDeliveryModal = true;
    }

    public function selectDelivery(string $method): void
    {
        if (in_array($method, ['delivery', 'pickup'], true)) {
            $this->deliveryMethod = $method;
            unset($this->deliveryQuote);
        }

        $this->showDeliveryModal = false;
    }

    public function applyCoupon(): void
    {
        $code = strtoupper(trim($this->couponInput));

        if ($code === '') {
            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            $this->addError('couponInput', 'Coupon code not found.');

            return;
        }

        $subtotalCents = (int) $this->lines->sum('line_total_cents');
        $error = $coupon->validateFor($subtotalCents, auth()->id());

        if ($error) {
            $this->addError('couponInput', $error);

            return;
        }

        $this->appliedCouponId = $coupon->id;
        $this->appliedCouponCode = $coupon->code;
        $this->discountCents = $coupon->discountFor($subtotalCents);
        $this->couponInput = '';
        $this->resetErrorBag('couponInput');

        Flux::toast(heading: 'Coupon applied', text: $coupon->valueLabel() . ' discount added.', variant: 'success');
    }

    public function removeCoupon(): void
    {
        $this->appliedCouponId = null;
        $this->appliedCouponCode = '';
        $this->discountCents = 0;
        $this->couponInput = '';
    }

    public function placeOrder(): void
    {
        // If the order was already placed in this session and is still awaiting
        // payment, just re-open the Paystack popup for it — don't create a
        // duplicate. This is what a re-click after dismissing the popup hits.
        if ($this->payOrderId) {
            $existing = Order::find($this->payOrderId);

            if ($existing && !$existing->isPaid()) {
                if (!$this->openPaystack($existing)) {
                    $this->addError('payment', 'We could not start the payment. Please try again.');
                }

                return;
            }
        }

        $lines = $this->lines;

        if ($lines->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $this->validate(['deliveryMethod' => ['required', 'in:delivery,pickup']]);

        $address = null;
        $subtotalCents = (int) $lines->sum('line_total_cents');

        $minOrderCents = app(\App\Settings\CheckoutSettings::class)->min_order_value * 100;
        if ($minOrderCents > 0 && $subtotalCents < $minOrderCents) {
            Flux::toast(heading: 'Order below minimum', text: 'The minimum order value is ' . money($minOrderCents) . '.', variant: 'warning');

            return;
        }

        if ($this->deliveryMethod === 'pickup') {
            $quote = new DeliveryQuoteResult(serviceable: true, feeCents: 0, isFree: true);
        } else {
            $address = auth()->user()->addresses()->find($this->selectedAddressId);

            if (!$address) {
                $this->addError('selectedAddressId', 'Select a delivery address or choose pickup.');

                return;
            }

            $quote = app(DeliveryResolver::class)->quoteDefault($address->latitude, $address->longitude, $subtotalCents);

            if (!$quote->serviceable) {
                $this->addError('selectedAddressId', "We don't deliver to this location yet — choose pickup or request a quote.");

                return;
            }
        }

        $tax = app(TaxCalculator::class);
        $vatCents = $tax->taxForCart($lines);
        $deliveryCents = $quote->feeCents;

        // Revalidate the coupon at placement time — it may have expired or hit
        // its limit between when the customer applied it and now.
        $coupon = null;
        if ($this->appliedCouponId) {
            $coupon = Coupon::find($this->appliedCouponId);
            if (!$coupon || $coupon->validateFor($subtotalCents, auth()->id()) !== null) {
                $this->removeCoupon();
                Flux::toast(heading: 'Coupon removed', text: 'Your coupon is no longer valid and was removed.', variant: 'warning');

                return;
            }
            $this->discountCents = $coupon->discountFor($subtotalCents);
        }

        $discountCents = $this->discountCents;
        // When prices already include tax the VAT is embedded in the subtotal,
        // so it must not be added again on top.
        $totalCents = $tax->pricesIncludeTax() ? max(0, $subtotalCents - $discountCents) + $deliveryCents : max(0, $subtotalCents - $discountCents) + $vatCents + $deliveryCents;

        $order = DB::transaction(function () use ($tax, $lines, $address, $quote, $subtotalCents, $vatCents, $deliveryCents, $discountCents, $totalCents, $coupon) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'address_id' => $address?->id,
                'delivery_zone_id' => $quote->zone?->id,
                'order_number' => Order::generateNumber(),
                'status' => OrderStatus::PENDING,
                'subtotal_cents' => $subtotalCents,
                'vat_cents' => $vatCents,
                'tax_inclusive' => $tax->pricesIncludeTax(),
                'delivery_cents' => $deliveryCents,
                'installation_cents' => 0,
                'discount_cents' => $discountCents,
                'total_cents' => $totalCents,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'payment_method' => null,
                'notes' => null,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $variant = $line['variant'];
                $rate = $tax->rateForProduct($product);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_snapshot' => [
                        'name' => $product->name . ($line['label'] ? ' — ' . $line['label'] : ''),
                        'sku' => $variant?->sku ?? $product->sku,
                        'model_number' => $product->model_number,
                    ],
                    'unit_price_cents' => $line['unit_price_cents'],
                    'quantity' => $line['qty'],
                    'line_total_cents' => $line['line_total_cents'],
                    'tax_rate' => $rate,
                    'tax_cents' => $tax->taxForLine((int) $line['line_total_cents'], $rate),
                ]);
            }

            if ($coupon && $discountCents > 0) {
                CouponUse::create([
                    'coupon_id' => $coupon->id,
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'discount_cents' => $discountCents,
                    'used_at' => now(),
                ]);
                $coupon->increment('uses_count');
            }

            return $order;
        });

        // With Paystack active, open its popup right here. Otherwise fall back to
        // the payment page (which also serves cash-on-delivery and other flows).
        if ($this->openPaystack($order)) {
            $this->payOrderId = $order->id;
        } else {
            $this->redirectRoute('payment.page', $order, navigate: true);
        }
    }
}; ?>

@assets
    <script src="https://js.paystack.co/v2/inline.js"></script>
@endassets

@php

    $tax = app(\App\Support\TaxCalculator::class);
    $quote = $this->deliveryQuote;
    $subtotalCents = $this->lines->sum('line_total_cents');
    $vatCents = $tax->taxForCart($this->lines);
    $taxInclusive = $tax->pricesIncludeTax();
    $vatRates = $this->lines->map(fn($l) => (float) $tax->rateForProduct($l['product']))->filter()->unique();
    $inclSpan = $taxInclusive ? ' <span class="text-xs opacity-60">(incl.)</span>' : '';
    $vatRateLabel =
        $vatRates->count() === 1
            ? 'VAT ' . rtrim(rtrim(number_format($vatRates->first(), 2), '0'), '.') . '%' . $inclSpan
            : 'VAT (mixed rates)' . $inclSpan;
    $deliveryCents = $quote->feeCents;
    $discountCents = $this->discountCents;
    $totalCents = $taxInclusive
        ? max(0, $subtotalCents - $discountCents) + $deliveryCents
        : max(0, $subtotalCents - $discountCents) + $vatCents + $deliveryCents;
    $unserviceable = $this->deliveryMethod === 'delivery' && $this->selectedAddress && !$quote->serviceable;

    $addressFilled = $this->selectedAddress !== null;
    $deliveryFilled = true;

    $deliveryLabels = [
        'delivery' => 'Deliver to address',
        'pickup' => 'Pickup in store',
    ];
@endphp

@include('partials.storefront.address-map-scripts')

<div class="page-fade" x-data="addressMap()"
    x-effect="($wire.showAddressModal && $wire.addressModalMode === 'create') ? open() : close()">

    {{-- Opens the Paystack popup when the order is placed (or re-clicked). --}}
    <div x-data="paystackCheckout" @paystack-open.window="open($event.detail.accessCode)"></div>

    {{-- Breadcrumb --}}
    <div class="bg-surface-sunken">
        <div class="shell py-3">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('cart')" wire:navigate>Cart</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Checkout</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="shell pt-6 pb-20">

        {{-- Page header --}}
        <h1 class="text-3xl font-semibold tracking-tight">Checkout</h1>

        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- ================================================== --}}
            {{-- LEFT: FORMS --}}
            {{-- ================================================== --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- Delivery address --}}
                <section class="rounded-md border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3">
                        <flux:heading size="sm" class="flex items-center gap-2 uppercase tracking-wide">
                            <flux:icon.check-circle variant="micro"
                                class="size-4 {{ $addressFilled ? 'text-emerald-500' : 'text-zinc-300' }}" />
                            Delivery address
                        </flux:heading>
                        @if ($this->addresses->isNotEmpty())
                            <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square"
                                wire:click="openAddressModal('select')">Select</flux:button>
                        @else
                            <flux:button type="button" variant="customer-outline" size="customer" icon="plus"
                                wire:click="openAddressModal('create')">Add</flux:button>
                        @endif
                    </div>

                    <div class="p-6">
                        @if ($this->deliveryMethod === 'pickup')
                            <p class="text-[13px] text-ink-3">Collecting from our Nairobi showroom — no delivery address
                                required.</p>
                        @elseif ($this->selectedAddress)
                            @php $address = $this->selectedAddress; @endphp
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-[10.5px] font-bold tracking-widest text-ink-3 uppercase">{{ $address->label }}</span>
                                @if ($address->is_default)
                                    <flux:badge color="lime" size="sm">Default</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 text-[14px] font-semibold text-ink">{{ $address->fullName() }}</div>
                            <div class="mt-1 text-[13px] leading-relaxed text-ink-2">{{ $address->oneLiner() }}</div>
                            @if ($address->phone)
                                <div class="mt-1 text-[12.5px] text-ink-3">{{ $address->phone }}</div>
                            @endif
                        @elseif ($this->addresses->isNotEmpty())
                            <p class="text-[13px] text-ink-3">Select a delivery address to continue.</p>
                        @else
                            <div class="rounded-md border border-dashed border-zinc-300 p-6 text-center">
                                <flux:icon.map-pin variant="outline" class="mx-auto size-7 text-ink-4" />
                                <p class="mt-2 text-[13px] text-ink-3">No saved addresses yet.</p>
                                <flux:button type="button" variant="customer-primary" size="customer" icon="plus"
                                    wire:click="openAddressModal('create')" class="mt-3">Add an address</flux:button>
                            </div>
                        @endif
                        @error('selectedAddressId')
                            <p class="mt-2 text-[12.5px] text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- Delivery method --}}
                <section class="rounded-md border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3">
                        <flux:heading size="sm" class="flex items-center gap-2 uppercase tracking-wide">
                            <flux:icon.check-circle variant="micro"
                                class="size-4 {{ $deliveryFilled ? 'text-emerald-500' : 'text-zinc-300' }}" />
                            Delivery method
                        </flux:heading>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square"
                            wire:click="openDeliveryModal">Change</flux:button>
                    </div>

                    <div class="flex items-start gap-3 p-6">
                        <flux:icon :name="$this->deliveryMethod === 'pickup' ? 'building-storefront' : 'truck'"
                            variant="micro" class="mt-0.5 size-4 text-brand-500" />
                        <div>
                            <div class="text-[13.5px] font-semibold text-ink">
                                {{ $deliveryLabels[$this->deliveryMethod] }}</div>
                            @if ($this->deliveryMethod === 'pickup')
                                <div class="mt-0.5 text-[12.5px] text-ink-3">Collect from our Nairobi showroom — free.
                                </div>
                            @elseif (!$this->selectedAddress)
                                <div class="mt-0.5 text-[12.5px] text-ink-3">Add a delivery address to see availability
                                    and cost.</div>
                            @elseif ($unserviceable)
                                <div class="mt-0.5 text-[12.5px] text-red-500">We don't deliver to this location yet.
                                    Choose pickup or request a quote.</div>
                            @else
                                <div class="mt-0.5 text-[12.5px] text-ink-3">
                                    {{ $quote->zone?->name }}{{ $quote->etaLabel ? ' · ' . $quote->etaLabel : '' }} —
                                    @if ($quote->isFree)
                                        <span
                                            class="font-semibold text-emerald-600">Free{{ $quote->promotionName ? ' (' . $quote->promotionName . ')' : '' }}</span>
                                    @else
                                        <span class="font-semibold text-ink-2">{!! money($quote->feeCents) !!}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>

            {{-- ================================================== --}}
            {{-- RIGHT: ORDER SUMMARY --}}
            {{-- ================================================== --}}
            <aside class="w-full shrink-0 space-y-4 lg:sticky lg:top-44 lg:w-96">
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-200 px-6 py-3">
                        <flux:heading size="sm" class="uppercase tracking-wide">Order summary</flux:heading>
                    </div>

                    <div class="p-6">
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>Subtotal</span>
                                <span class="font-medium tabular-nums">{!! money($subtotalCents) !!}</span>
                            </div>
                            @if ($discountCents > 0)
                                <div class="flex items-center justify-between text-sm text-emerald-600">
                                    <span>Discount ({{ $this->appliedCouponCode }})</span>
                                    <span class="font-medium tabular-nums">−{!! money($discountCents) !!}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>{{ $this->deliveryMethod === 'pickup' ? 'Pickup' : 'Shipping' }}</span>
                                @if ($unserviceable)
                                    <span class="font-medium text-red-500">Unavailable</span>
                                @else
                                    <span
                                        class="{{ $deliveryCents === 0 ? 'font-medium text-emerald-600' : 'font-medium tabular-nums' }}">
                                        {!! $deliveryCents === 0 ? 'Free' : money($deliveryCents) !!}
                                    </span>
                                @endif
                            </div>
                            @if ($tax->enabled() && $vatCents > 0)
                                <div class="flex items-center justify-between text-sm text-ink-2">
                                    <span>{!! $vatRateLabel !!}</span>
                                    <span class="font-medium tabular-nums">{!! money($vatCents) !!}</span>
                                </div>
                            @endif
                        </div>

                        <div class="my-5 h-px bg-zinc-100"></div>

                        <div class="flex items-center justify-between">
                            <span class="text-[13px] font-bold tracking-wide uppercase">Total</span>
                            <span class="text-2xl font-bold text-brand-500 tabular-nums">{!! money($totalCents) !!}</span>
                        </div>

                        <flux:button variant="customer-primary" size="customer-lg" wire:click="placeOrder"
                            icon:trailing="chevron-right" class="mt-5! w-full!" wire:loading.attr="disabled"
                            wire:target="placeOrder">
                            Continue to payment
                        </flux:button>

                        <div class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                            <flux:icon.shield-check variant="micro" class="size-3.5" />
                            SSL encrypted &amp; secure
                        </div>

                        <div class="mt-4 border-t border-zinc-100 pt-4 text-center text-[12px] text-ink-3">
                            Need a formal quote for a tender?
                            <a href="{{ route('quote.request') }}" wire:navigate
                                class="font-semibold text-brand-500 hover:text-brand-600">Request a quote</a>
                        </div>
                    </div>
                </div>

                {{-- Promo code --}}
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="px-6 pt-4 text-center">
                        <flux:heading size="sm">Have a promo code?</flux:heading>
                    </div>

                    <div class="px-6 pb-6 pt-4">
                        @if ($this->appliedCouponCode)
                            <div class="flex items-center justify-between rounded-md bg-emerald-50 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <flux:icon.ticket variant="micro" class="size-4 text-emerald-600" />
                                    <span
                                        class="font-mono text-[12px] font-semibold text-emerald-700">{{ $this->appliedCouponCode }}</span>
                                </div>
                                <button type="button" wire:click="removeCoupon"
                                    class="text-[11px] text-emerald-600 hover:text-red-500">Remove</button>
                            </div>
                        @else
                            <flux:input.group>
                                <flux:input wire:model="couponInput" placeholder="Coupon code" class="text-[13px]!"
                                    wire:keydown.enter.prevent="applyCoupon" />
                                <flux:button type="button" variant="primary" wire:click="applyCoupon"
                                    wire:loading.attr="disabled" wire:target="applyCoupon">
                                    Apply
                                </flux:button>
                            </flux:input.group>
                            @error('couponInput')
                                <p class="mt-1 text-[11.5px] text-red-500">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Address modal — select an existing address or add a new one --}}
    @include('partials.storefront.address-modal')

    {{-- Delivery method modal --}}
    <flux:modal wire:model.self="showDeliveryModal" class="md:w-[520px]">
        <flux:heading class="uppercase tracking-wide">Delivery method</flux:heading>
        <flux:subheading>How would you like to receive your order?</flux:subheading>

        <div class="mt-5 grid gap-3">
            @foreach (['delivery' => 'Deliver to address', 'pickup' => 'Pickup in store'] as $value => $title)
                <button type="button" wire:click="selectDelivery('{{ $value }}')"
                    class="flex items-start gap-3 rounded-md border p-4 text-left transition {{ $this->deliveryMethod === $value ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                    <flux:icon :name="$value === 'pickup' ? 'building-storefront' : 'truck'" variant="micro"
                        class="mt-0.5 size-4 {{ $this->deliveryMethod === $value ? 'text-brand-500' : 'text-ink-4' }}" />
                    <div>
                        <div class="text-[13.5px] font-semibold text-ink">{{ $title }}</div>
                        <div class="mt-0.5 text-[12px] text-ink-3">
                            {{ $value === 'pickup' ? 'Collect from our Nairobi showroom — free.' : 'Delivery across Nairobi & nearby areas — free for launch.' }}
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    </flux:modal>
</div>

@script
    <script>
        Alpine.data('paystackCheckout', () => ({
            processing: false,

            open(accessCode) {
                if (!accessCode || typeof PaystackPop === 'undefined') {
                    return;
                }

                const popup = new PaystackPop();

                popup.resumeTransaction(accessCode, {
                    onSuccess: (transaction) => {
                        this.processing = true;
                        this.$wire.verifyPayment(transaction.reference);
                    },
                    // Dismissed: stay on checkout — the customer can click the
                    // button again to reopen the popup for the same order.
                    onCancel: () => {
                        this.processing = false;
                    },
                    onError: () => {
                        this.processing = false;
                    },
                });
            },
        }));
    </script>
@endscript
