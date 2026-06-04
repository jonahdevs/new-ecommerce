<?php

use App\Enums\OrderStatus;
use App\Models\Address;
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

new #[Layout('layouts::storefront')] #[Title('Checkout')] class extends Component
{
    public ?int $selectedAddressId = null;

    public string $deliveryMethod = 'delivery';

    // ==================================================
    // ADDRESS FORM MODALS
    // ==================================================
    public bool $showAddressModal = false;

    public string $addressModalMode = 'select';

    public bool $showDeliveryModal = false;

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public string $alternative_phone = '';

    public string $line1 = '';

    public string $delivery_instructions = '';

    public bool $is_default = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

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

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function selectedAddress(): ?Address
    {
        if (! $this->selectedAddressId) {
            return null;
        }

        return $this->addresses->firstWhere('id', $this->selectedAddressId);
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

        return app(DeliveryResolver::class)->quoteDefault(
            $address?->latitude,
            $address?->longitude,
            $subtotalCents,
        );
    }

    /**
     * The zone the in-progress map pin falls into, for live feedback while
     * adding an address.
     */
    #[Computed]
    public function pinnedZone(): ?\App\Models\DeliveryZone
    {
        return app(DeliveryResolver::class)->resolveZone($this->latitude, $this->longitude);
    }

    public function addressRules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function openAddressModal(string $mode = 'select'): void
    {
        $this->resetValidation();

        if ($mode === 'create' || $this->addresses->isEmpty()) {
            $this->prepareAddressForm();
            $this->addressModalMode = 'create';
        } else {
            $this->addressModalMode = 'select';
        }

        $this->showAddressModal = true;
    }

    public function startAddressCreate(): void
    {
        $this->resetValidation();
        $this->prepareAddressForm();
        $this->addressModalMode = 'create';
    }

    private function prepareAddressForm(): void
    {
        $this->reset(['label', 'name', 'phone', 'alternative_phone', 'line1', 'delivery_instructions', 'is_default', 'latitude', 'longitude']);
        $this->label = 'Home';
    }

    public function selectAddress(int $id): void
    {
        if ($this->addresses->contains('id', $id)) {
            $this->selectedAddressId = $id;
            unset($this->selectedAddress, $this->deliveryQuote);
        }

        $this->showAddressModal = false;
    }

    public function saveAddress(): void
    {
        $data = $this->validate($this->addressRules());

        if ($data['is_default']) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if (auth()->user()->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $data['delivery_zone_id'] = app(DeliveryResolver::class)
            ->resolveZone($data['latitude'] ?? null, $data['longitude'] ?? null)?->id;

        $address = auth()->user()->addresses()->create($data);

        $this->selectedAddressId = $address->id;
        $this->showAddressModal = false;
        unset($this->addresses, $this->selectedAddress, $this->deliveryQuote);

        Flux::toast(heading: 'Address added', text: 'Your delivery address has been saved.', variant: 'success');
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

    public function placeOrder(): void
    {
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
            Flux::toast(heading: 'Order below minimum', text: 'The minimum order value is '.money($minOrderCents).'.', variant: 'warning');

            return;
        }

        if ($this->deliveryMethod === 'pickup') {
            $quote = new DeliveryQuoteResult(serviceable: true, feeCents: 0, isFree: true);
        } else {
            $address = auth()->user()->addresses()->find($this->selectedAddressId);

            if (! $address) {
                $this->addError('selectedAddressId', 'Select a delivery address or choose pickup.');

                return;
            }

            $quote = app(DeliveryResolver::class)->quoteDefault(
                $address->latitude,
                $address->longitude,
                $subtotalCents,
            );

            if (! $quote->serviceable) {
                $this->addError('selectedAddressId', "We don't deliver to this location yet — choose pickup or request a quote.");

                return;
            }
        }

        $tax = app(TaxCalculator::class);
        $vatCents = $tax->taxForCart($lines);
        $deliveryCents = $quote->feeCents;
        // When prices already include tax the VAT is embedded in the subtotal,
        // so it must not be added again on top.
        $totalCents = $tax->pricesIncludeTax()
            ? $subtotalCents + $deliveryCents
            : $subtotalCents + $vatCents + $deliveryCents;

        $order = DB::transaction(function () use ($tax, $lines, $address, $quote, $subtotalCents, $vatCents, $deliveryCents, $totalCents) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'address_id' => $address?->id,
                'delivery_zone_id' => $quote->zone?->id,
                'order_number' => Order::generateNumber(),
                'status' => OrderStatus::PENDING,
                'subtotal_cents' => $subtotalCents,
                'vat_cents' => $vatCents,
                'delivery_cents' => $deliveryCents,
                'installation_cents' => 0,
                'total_cents' => $totalCents,
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
                    'product_name' => $product->name.($line['label'] ? ' — '.$line['label'] : ''),
                    'product_sku' => $variant?->sku ?? $product->sku,
                    'unit_price_cents' => $line['unit_price_cents'],
                    'quantity' => $line['qty'],
                    'line_total_cents' => $line['line_total_cents'],
                    'tax_rate' => $rate,
                    'tax_cents' => $tax->taxForLine((int) $line['line_total_cents'], $rate),
                ]);
            }

            return $order;
        });

        $this->redirectRoute('payment.page', $order, navigate: true);
    }
}; ?>

@php

    $tax           = app(\App\Support\TaxCalculator::class);
    $quote         = $this->deliveryQuote;
    $subtotalCents = $this->lines->sum('line_total_cents');
    $vatCents      = $tax->taxForCart($this->lines);
    $taxInclusive  = $tax->pricesIncludeTax();
    $deliveryCents = $quote->feeCents;
    $totalCents    = $taxInclusive
        ? $subtotalCents + $deliveryCents
        : $subtotalCents + $vatCents + $deliveryCents;
    $unserviceable = $this->deliveryMethod === 'delivery' && $this->selectedAddress && ! $quote->serviceable;

    $addressFilled = $this->selectedAddress !== null;
    $deliveryFilled = true;

    $deliveryLabels = [
        'delivery' => 'Deliver to address',
        'pickup'   => 'Pickup in store',
    ];
@endphp

@include('partials.storefront.address-map-scripts')

<div class="page-fade" x-data="addressMap()"
     x-effect="($wire.showAddressModal && $wire.addressModalMode === 'create') ? open() : close()">
    <div class="shell pt-4 pb-20">

        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('cart')" wire:navigate>Cart</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Checkout</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Page header --}}
        <h1 class="text-3xl font-semibold tracking-tight">Checkout</h1>

        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- ================================================== --}}
            {{-- LEFT: FORMS --}}
            {{-- ================================================== --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- Delivery address --}}
                <section class="rounded-md border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                        <h2 class="flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            <flux:icon.check-circle variant="micro" class="size-4 {{ $addressFilled ? 'text-emerald-500' : 'text-zinc-300' }}" />
                            Delivery address
                        </h2>
                        @if ($this->addresses->isNotEmpty())
                            <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square" wire:click="openAddressModal('select')">Select</flux:button>
                        @else
                            <flux:button type="button" variant="customer-outline" size="customer" icon="plus" wire:click="openAddressModal('create')">Add</flux:button>
                        @endif
                    </div>

                    <div class="p-6">
                        @if ($this->deliveryMethod === 'pickup')
                            <p class="text-[13px] text-ink-3">Collecting from our Nairobi showroom — no delivery address required.</p>
                        @elseif ($this->selectedAddress)
                            @php $address = $this->selectedAddress; @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-[10.5px] font-bold tracking-[0.1em] text-ink-3 uppercase">{{ $address->label }}</span>
                                @if ($address->is_default)
                                    <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-[9.5px] font-bold tracking-wide text-brand-500 uppercase">Default</span>
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
                                <flux:button type="button" variant="customer-primary" size="customer" icon="plus" wire:click="openAddressModal('create')" class="mt-3">Add an address</flux:button>
                            </div>
                        @endif
                        @error('selectedAddressId')
                            <p class="mt-2 text-[12.5px] text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- Delivery method --}}
                <section class="rounded-md border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                        <h2 class="flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            <flux:icon.check-circle variant="micro" class="size-4 {{ $deliveryFilled ? 'text-emerald-500' : 'text-zinc-300' }}" />
                            Delivery method
                        </h2>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square" wire:click="openDeliveryModal">Change</flux:button>
                    </div>

                    <div class="flex items-start gap-3 p-6">
                        <flux:icon :name="$this->deliveryMethod === 'pickup' ? 'building-storefront' : 'truck'" variant="micro" class="mt-0.5 size-4 text-brand-500" />
                        <div>
                            <div class="text-[13.5px] font-semibold text-ink">{{ $deliveryLabels[$this->deliveryMethod] }}</div>
                            @if ($this->deliveryMethod === 'pickup')
                                <div class="mt-0.5 text-[12.5px] text-ink-3">Collect from our Nairobi showroom — free.</div>
                            @elseif (! $this->selectedAddress)
                                <div class="mt-0.5 text-[12.5px] text-ink-3">Add a delivery address to see availability and cost.</div>
                            @elseif ($unserviceable)
                                <div class="mt-0.5 text-[12.5px] text-red-500">We don't deliver to this location yet. Choose pickup or request a quote.</div>
                            @else
                                <div class="mt-0.5 text-[12.5px] text-ink-3">
                                    {{ $quote->zone?->name }}{{ $quote->etaLabel ? ' · '.$quote->etaLabel : '' }} —
                                    @if ($quote->isFree)
                                        <span class="font-semibold text-emerald-600">Free{{ $quote->promotionName ? ' ('.$quote->promotionName.')' : '' }}</span>
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
            <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-96">
                <div class="rounded-md border border-zinc-200 bg-white">
                    <div class="border-b border-zinc-200 px-6 py-4">
                        <h2 class="text-[11px] font-bold tracking-[0.14em] text-ink uppercase">Order summary</h2>
                    </div>

                    <div class="p-6">
                    {{-- Items --}}
                    <div class="space-y-3">
                        @foreach ($this->lines as $line)
                            <div wire:key="sum-{{ $line['key'] }}" class="flex items-center gap-3">
                                <div class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                    @if ($line['product']->cover_url)
                                        <img src="{{ $line['product']->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-[12.5px] font-semibold text-ink">{{ $line['product']->name }}</div>
                                    @if ($line['label'])
                                        <div class="truncate text-[11px] text-ink-3">{{ $line['label'] }}</div>
                                    @endif
                                    <div class="text-[11.5px] text-ink-4">Qty {{ $line['qty'] }}</div>
                                </div>
                                <div class="text-[12.5px] font-semibold text-ink tabular-nums whitespace-nowrap">{!! money($line['line_total_cents']) !!}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="my-5 h-px bg-zinc-100"></div>

                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between text-sm text-ink-2">
                            <span>Subtotal</span>
                            <span class="font-medium tabular-nums">{!! money($subtotalCents) !!}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-ink-2">
                            <span>{{ $this->deliveryMethod === 'pickup' ? 'Pickup' : 'Shipping' }}</span>
                            @if ($unserviceable)
                                <span class="font-medium text-red-500">Unavailable</span>
                            @else
                                <span class="{{ $deliveryCents === 0 ? 'font-medium text-emerald-600' : 'font-medium tabular-nums' }}">
                                    {!! $deliveryCents === 0 ? 'Free' : money($deliveryCents) !!}
                                </span>
                            @endif
                        </div>
                        @if ($tax->enabled() && $vatCents > 0)
                            <div class="flex items-center justify-between text-sm text-ink-2">
                                <span>VAT{{ $taxInclusive ? ' (incl.)' : '' }}</span>
                                <span class="font-medium tabular-nums">{!! money($vatCents) !!}</span>
                            </div>
                        @endif
                    </div>

                    <div class="my-5 h-px bg-zinc-100"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-[13px] font-bold tracking-wide uppercase">Total</span>
                        <span class="text-2xl font-bold text-brand-500 tabular-nums">{!! money($totalCents) !!}</span>
                    </div>

                    <flux:button variant="customer-primary" size="customer-lg" wire:click="placeOrder" icon:trailing="arrow-right" class="mt-5! w-full!" wire:loading.attr="disabled" wire:target="placeOrder">
                        Continue to payment
                    </flux:button>

                    <div class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-ink-4">
                        <flux:icon.shield-check variant="micro" class="size-3.5" />
                        SSL encrypted &amp; secure
                    </div>

                    <div class="mt-4 border-t border-zinc-100 pt-4 text-center text-[12px] text-ink-3">
                        Need a formal quote for a tender?
                        <a href="{{ route('quote.request') }}" wire:navigate class="font-semibold text-brand-500 hover:text-brand-600">Request a quote</a>
                    </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- Address modal — select an existing address or add a new one --}}
    <flux:modal wire:model.self="showAddressModal" class="md:w-[560px]" :dismissible="false">
        @if ($addressModalMode === 'select')
            <flux:heading>Choose a delivery address</flux:heading>
            <flux:subheading>Select where you'd like this order delivered.</flux:subheading>

            <div class="mt-5 space-y-3">
                @foreach ($this->addresses as $address)
                    <button type="button" wire:key="modal-addr-{{ $address->id }}"
                            wire:click="selectAddress({{ $address->id }})"
                            class="block w-full rounded-md border p-4 text-left transition {{ $this->selectedAddressId === $address->id ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-[10.5px] font-bold tracking-[0.1em] text-ink-3 uppercase">{{ $address->label }}</span>
                            @if ($address->is_default)
                                <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-[9.5px] font-bold tracking-wide text-brand-500 uppercase">Default</span>
                            @endif
                        </div>
                        <div class="mt-1 text-[13.5px] font-semibold text-ink">{{ $address->fullName() }}</div>
                        <div class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $address->oneLiner() }}</div>
                        @if ($address->phone)
                            <div class="mt-1 text-[12px] text-ink-3">{{ $address->phone }}</div>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                <flux:button type="button" variant="customer-outline" size="customer" icon="plus" wire:click="startAddressCreate">Add new address</flux:button>
            </div>
        @else
            <flux:heading>New address</flux:heading>
            <flux:subheading>
                <span x-show="step === 1">Pin where you'd like this order delivered.</span>
                <span x-show="step === 2" x-cloak>Now fill in the delivery address details.</span>
            </flux:subheading>

            <form wire:submit="saveAddress" class="mt-6">

                {{-- Step 1 — pin the location on the map --}}
                <div x-show="step === 1" class="space-y-3">
                    @include('partials.storefront.address-map-pin')

                    <div class="flex justify-end gap-3 pt-2">
                        @if ($this->addresses->isNotEmpty())
                            <flux:button type="button" variant="ghost" icon="arrow-left" wire:click="$set('addressModalMode', 'select')">Back</flux:button>
                        @else
                            <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                        @endif
                        <flux:button type="button" variant="customer-primary" size="customer" icon:trailing="arrow-right" x-on:click="showDetails()">Next</flux:button>
                    </div>
                </div>

                {{-- Step 2 — address details --}}
                <div x-show="step === 2" x-cloak class="space-y-4">
                    @include('partials.storefront.address-fields')

                    <div class="flex justify-between gap-3 pt-2">
                        <flux:button type="button" variant="ghost" icon="arrow-left" x-on:click="showLocation()">Back</flux:button>
                        <flux:button type="submit" variant="customer-primary" size="customer">Add address</flux:button>
                    </div>
                </div>
            </form>
        @endif
    </flux:modal>

    {{-- Delivery method modal --}}
    <flux:modal wire:model.self="showDeliveryModal" class="md:w-[520px]">
        <flux:heading>Delivery method</flux:heading>
        <flux:subheading>How would you like to receive your order?</flux:subheading>

        <div class="mt-5 grid gap-3">
            @foreach (['delivery' => 'Deliver to address', 'pickup' => 'Pickup in store'] as $value => $title)
                <button type="button" wire:click="selectDelivery('{{ $value }}')"
                        class="flex items-start gap-3 rounded-md border p-4 text-left transition {{ $this->deliveryMethod === $value ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                    <flux:icon :name="$value === 'pickup' ? 'building-storefront' : 'truck'" variant="micro" class="mt-0.5 size-4 {{ $this->deliveryMethod === $value ? 'text-brand-500' : 'text-ink-4' }}" />
                    <div>
                        <div class="text-[13.5px] font-semibold text-ink">{{ $title }}</div>
                        <div class="mt-0.5 text-[12px] text-ink-3">{{ $value === 'pickup' ? 'Collect from our Nairobi showroom — free.' : 'Delivery across Nairobi & nearby areas — free for launch.' }}</div>
                    </div>
                </button>
            @endforeach
        </div>
    </flux:modal>
</div>
