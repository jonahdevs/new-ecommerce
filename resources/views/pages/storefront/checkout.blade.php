<?php

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\StorefrontSession;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Checkout — Sheffield')] class extends Component
{
    public ?int $selectedAddressId = null;

    public string $deliveryMethod = 'delivery';

    public string $paymentMethod = 'mpesa';

    // ─── Modals ──────────────────────────────────────────────────────────────
    public bool $showAddressModal = false;

    public string $addressModalMode = 'select';

    public bool $showDeliveryModal = false;

    public bool $showPaymentModal = false;

    public string $label = 'Home';

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $line1 = '';

    public string $line2 = '';

    public string $city = 'Nairobi';

    public string $postal_code = '';

    public string $country = 'KE';

    public bool $is_default = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    /** @var array<int, string> */
    public array $paymentMethods = ['mpesa', 'card', 'bank_transfer', 'net_30'];

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        if (StorefrontSession::cartLines()->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $this->selectedAddressId = auth()->user()->addresses()
            ->orderByDesc('is_default')
            ->value('id');
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

    public function addressRules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
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
        $this->reset(['label', 'first_name', 'last_name', 'phone', 'line1', 'line2', 'city', 'postal_code', 'country', 'is_default', 'latitude', 'longitude']);
        $this->label = 'Home';
        $this->city = 'Nairobi';
        $this->country = 'KE';
    }

    public function selectAddress(int $id): void
    {
        if ($this->addresses->contains('id', $id)) {
            $this->selectedAddressId = $id;
            unset($this->selectedAddress);
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

        $address = auth()->user()->addresses()->create($data);

        $this->selectedAddressId = $address->id;
        $this->showAddressModal = false;
        unset($this->addresses, $this->selectedAddress);

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
        }

        $this->showDeliveryModal = false;
    }

    public function openPaymentModal(): void
    {
        $this->showPaymentModal = true;
    }

    public function selectPayment(string $method): void
    {
        if (in_array($method, $this->paymentMethods, true)) {
            $this->paymentMethod = $method;
        }

        $this->showPaymentModal = false;
    }

    public function placeOrder(): void
    {
        $lines = $this->lines;

        if ($lines->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $this->validate([
            'paymentMethod' => ['required', 'in:'.implode(',', $this->paymentMethods)],
            'deliveryMethod' => ['required', 'in:delivery,pickup'],
        ]);

        $address = null;

        if ($this->deliveryMethod === 'delivery') {
            $address = auth()->user()->addresses()->find($this->selectedAddressId);

            if (! $address) {
                $this->addError('selectedAddressId', 'Select a delivery address or choose pickup.');

                return;
            }
        }

        $subtotalCents = (int) $lines->sum('line_total_cents');
        $vatCents = (int) round($subtotalCents * 0.16);
        $deliveryCents = $this->deliveryMethod === 'pickup' ? 0 : ($subtotalCents > 50000000 ? 0 : 1200000);
        $totalCents = $subtotalCents + $vatCents + $deliveryCents;

        $order = DB::transaction(function () use ($lines, $address, $subtotalCents, $vatCents, $deliveryCents, $totalCents) {
            $order = Order::create([
                'user_id' => auth()->id(),
                'address_id' => $address?->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::PENDING,
                'subtotal_cents' => $subtotalCents,
                'vat_cents' => $vatCents,
                'delivery_cents' => $deliveryCents,
                'installation_cents' => 0,
                'total_cents' => $totalCents,
                'payment_method' => $this->paymentMethod,
                'notes' => null,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price_cents' => $product->sale_price ?? $product->price ?? 0,
                    'quantity' => $line['qty'],
                    'line_total_cents' => $line['line_total_cents'],
                ]);
            }

            return $order;
        });

        StorefrontSession::clearCart();
        $this->dispatch('cart-updated');

        Flux::toast(heading: 'Order placed', text: 'Order '.$order->order_number.' has been received.', variant: 'success');

        $this->redirectRoute('account.orders.show', $order, navigate: true);
    }

    private function generateOrderNumber(): string
    {
        $sequence = Order::whereYear('created_at', now()->year)->count() + 1;

        return 'SHF-'.now()->year.'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;' . number_format(intdiv($cents, 100), 0, '.', ',');

    $subtotalCents = $this->lines->sum('line_total_cents');
    $vatCents      = (int) round($subtotalCents * 0.16);
    $deliveryCents = $this->deliveryMethod === 'pickup' ? 0 : ($subtotalCents > 50000000 ? 0 : 1200000);
    $totalCents    = $subtotalCents + $vatCents + $deliveryCents;

    $paymentLabels = [
        'mpesa'         => 'M-Pesa',
        'card'          => 'Card',
        'bank_transfer' => 'Bank transfer',
        'net_30'        => 'Net 30 (invoice)',
    ];

    $deliveryLabels = [
        'delivery' => 'Deliver to address',
        'pickup'   => 'Pickup in store',
    ];

    $deliveryDescriptions = [
        'delivery' => 'Free within Nairobi over KES 500,000.',
        'pickup'   => 'Collect from our Nairobi showroom — free.',
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
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-semibold tracking-tight">Checkout</h1>
            <flux:button variant="ghost" size="customer" icon="arrow-left" :href="route('cart')" wire:navigate>
                Back to cart
            </flux:button>
        </div>

        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- ── Left: forms ── --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- Delivery address --}}
                <section class="rounded-md border border-zinc-200 bg-white p-6">
                    <div class="-mx-6 flex items-center justify-between border-b border-zinc-200 px-6 pb-4">
                        <h2 class="flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            <flux:icon.map-pin variant="micro" class="size-4 text-brand-500" />
                            Delivery address
                        </h2>
                        @if ($this->addresses->isNotEmpty())
                            <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square" wire:click="openAddressModal('select')">Select</flux:button>
                        @else
                            <flux:button type="button" variant="customer-outline" size="customer" icon="plus" wire:click="openAddressModal('create')">Add</flux:button>
                        @endif
                    </div>

                    <div class="mt-5">
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
                <section class="rounded-md border border-zinc-200 bg-white p-6">
                    <div class="-mx-6 flex items-center justify-between border-b border-zinc-200 px-6 pb-4">
                        <h2 class="flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            <flux:icon.truck variant="micro" class="size-4 text-brand-500" />
                            Delivery method
                        </h2>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square" wire:click="openDeliveryModal">Change</flux:button>
                    </div>

                    <div class="mt-5 flex items-start gap-3">
                        <flux:icon :name="$this->deliveryMethod === 'pickup' ? 'building-storefront' : 'truck'" variant="micro" class="mt-0.5 size-4 text-brand-500" />
                        <div>
                            <div class="text-[13.5px] font-semibold text-ink">{{ $deliveryLabels[$this->deliveryMethod] }}</div>
                            <div class="mt-0.5 text-[12.5px] text-ink-3">{{ $deliveryDescriptions[$this->deliveryMethod] }}</div>
                        </div>
                    </div>
                </section>

                {{-- Payment method --}}
                <section class="rounded-md border border-zinc-200 bg-white p-6">
                    <div class="-mx-6 flex items-center justify-between border-b border-zinc-200 px-6 pb-4">
                        <h2 class="flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-ink uppercase">
                            <flux:icon.credit-card variant="micro" class="size-4 text-brand-500" />
                            Payment method
                        </h2>
                        <flux:button type="button" variant="customer-outline" size="customer" icon="pencil-square" wire:click="openPaymentModal">Change</flux:button>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <flux:icon.credit-card variant="micro" class="size-4 text-brand-500" />
                        <span class="text-[13.5px] font-semibold text-ink">{{ $paymentLabels[$this->paymentMethod] }}</span>
                    </div>
                    @error('paymentMethod')
                        <p class="mt-2 text-[12.5px] text-red-500">{{ $message }}</p>
                    @enderror
                </section>
            </div>

            {{-- ── Right: order summary ── --}}
            <aside class="w-full shrink-0 lg:sticky lg:top-44 lg:w-96">
                <div class="rounded-md border border-zinc-200 bg-white p-6">
                    <div class="-mx-6 border-b border-zinc-200 px-6 pb-4">
                        <h2 class="text-[11px] font-bold tracking-[0.14em] text-ink uppercase">Order summary</h2>
                    </div>

                    {{-- Items --}}
                    <div class="mt-4 space-y-3">
                        @foreach ($this->lines as $line)
                            <div wire:key="sum-{{ $line['slug'] }}" class="flex items-center gap-3">
                                <div class="size-12 shrink-0 overflow-hidden rounded border border-zinc-100 bg-surface-sunken p-1">
                                    @if ($line['product']->cover_url)
                                        <img src="{{ $line['product']->cover_url }}" alt="" class="size-full object-contain" loading="lazy" />
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-[12.5px] font-semibold text-ink">{{ $line['product']->name }}</div>
                                    <div class="text-[11.5px] text-ink-4">Qty {{ $line['qty'] }}</div>
                                </div>
                                <div class="text-[12.5px] font-semibold text-ink tabular-nums whitespace-nowrap">{!! $kes($line['line_total_cents']) !!}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="my-5 h-px bg-zinc-100"></div>

                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between text-sm text-ink-2">
                            <span>Subtotal</span>
                            <span class="font-medium tabular-nums">{!! $kes($subtotalCents) !!}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-ink-2">
                            <span>{{ $this->deliveryMethod === 'pickup' ? 'Pickup' : 'Shipping' }}</span>
                            <span class="{{ $deliveryCents === 0 ? 'font-medium text-emerald-600' : 'font-medium tabular-nums' }}">
                                {!! $deliveryCents === 0 ? 'Free' : $kes($deliveryCents) !!}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-ink-2">
                            <span>VAT (16%)</span>
                            <span class="font-medium tabular-nums">{!! $kes($vatCents) !!}</span>
                        </div>
                    </div>

                    <div class="my-5 h-px bg-zinc-100"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-[13px] font-bold tracking-wide uppercase">Total</span>
                        <span class="text-2xl font-bold text-brand-500 tabular-nums">{!! $kes($totalCents) !!}</span>
                    </div>

                    <flux:button variant="customer-primary" size="customer-lg" wire:click="placeOrder" icon:trailing="arrow-right" class="mt-5! w-full!">
                        Place order
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
                <flux:button type="button" variant="ghost" x-on:click="$flux.close()">Cancel</flux:button>
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
                            <flux:button type="button" variant="ghost" x-on:click="$flux.close()">Cancel</flux:button>
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
                        <div class="mt-0.5 text-[12px] text-ink-3">{{ $value === 'pickup' ? 'Collect from our Nairobi showroom — free.' : 'Free within Nairobi over KES 500,000.' }}</div>
                    </div>
                </button>
            @endforeach
        </div>
    </flux:modal>

    {{-- Payment method modal --}}
    <flux:modal wire:model.self="showPaymentModal" class="md:w-[520px]">
        <flux:heading>Payment method</flux:heading>
        <flux:subheading>Choose how you'd like to pay.</flux:subheading>

        <div class="mt-5 grid gap-3">
            @foreach ($paymentLabels as $value => $title)
                <button type="button" wire:click="selectPayment('{{ $value }}')"
                        class="flex items-center gap-3 rounded-md border p-4 text-left transition {{ $this->paymentMethod === $value ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                    <span class="flex size-4 items-center justify-center rounded-full border {{ $this->paymentMethod === $value ? 'border-brand-500' : 'border-zinc-300' }}">
                        @if ($this->paymentMethod === $value)
                            <span class="size-2 rounded-full bg-brand-500"></span>
                        @endif
                    </span>
                    <span class="text-[13.5px] font-semibold text-ink">{{ $title }}</span>
                </button>
            @endforeach
        </div>
    </flux:modal>
</div>
