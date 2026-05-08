<?php

use App\Models\{Address, Area, County};
use App\Services\{CartService, CheckoutSession, Shipping\ShippingCalculator, Shipping\ShippingOption, Payment\PaymentService};
use Livewire\Attributes\{Computed, Layout, On};
use Livewire\Component;
use App\Livewire\Forms\CustomerAddressForm;
use App\Livewire\Concerns\HasAddressForm;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.checkout')] class extends Component {
    use HasAddressForm;

    // --- Forms & State ---
    public CustomerAddressForm $form;
    public string $selectedMethod = '';
    public ?int $selectedStationId = null;
    public string $paymentMethod = 'mpesa';

    // --- Selection UI ---
    public ?int $selectedAddressId = null;
    public bool $isEditingAddress = false;

    // --- Modal States ---
    public bool $showAddressPickerModal = false;
    public bool $showAddressFormModal = false;
    public bool $showShippingModal = false;
    public bool $showPaymentModal = false;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $cartService = app(CartService::class);
        $checkoutSession = app(CheckoutSession::class);

        if (!$cartService->hasItems()) {
            $this->redirectRoute('cart', navigate: true);
            return;
        }

        // Initialize state from session
        $this->selectedAddressId = $checkoutSession->getAddressId() ?? (auth()->user()->addresses()->where('is_default', true)->value('id') ?? auth()->user()->addresses()->oldest()->value('id'));

        if ($this->selectedAddressId) {
            $checkoutSession->setAddressId($this->selectedAddressId);
        }

        if ($checkoutSession->hasShipping()) {
            $this->selectedMethod = $checkoutSession->getShipping()['method_code'] ?? '';
            $this->selectedStationId = $checkoutSession->getPickupStationId();
        }

        if ($checkoutSession->hasPaymentMethod()) {
            $this->paymentMethod = $checkoutSession->getPaymentMethod();
        }
    }

    // =========================================================================
    // COMPUTED
    // =========================================================================

    #[Computed]
    public function address(): ?Address
    {
        return $this->selectedAddressId ? Address::with(['county', 'area', 'shippingZone'])->find($this->selectedAddressId) : null;
    }

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->oldest()->get();
    }

    #[Computed]
    public function shippingOptions(): \Illuminate\Support\Collection
    {
        if (!$this->address) {
            return collect();
        }

        $cartService = app(CartService::class);
        return app(ShippingCalculator::class)->calculate(countyId: $this->address->county_id, areaId: $this->address->area_id, weightKg: $cartService->getWeight(), orderAmount: $cartService->getSubtotal());
    }

    #[Computed]
    public function selectedOption(): ?ShippingOption
    {
        return $this->shippingOptions->firstWhere('methodCode', $this->selectedMethod);
    }

    #[Computed]
    public function currentPusOption(): ?ShippingOption
    {
        if (!$this->selectedOption?->isPus() || !$this->selectedStationId) {
            return $this->selectedOption;
        }

        return app(ShippingCalculator::class)->recalculateForStation(option: $this->selectedOption, stationId: $this->selectedStationId, weightKg: app(CartService::class)->getWeight());
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
    public function cartItems()
    {
        return app(CartService::class)
            ->getCart()
            ->items()
            ->with(['product', 'variant'])
            ->get();
    }

    #[Computed]
    public function cartSummary(): array
    {
        $cartService = app(CartService::class);
        $summary = $cartService->summary($cartService->getCart());

        return array_merge($summary, [
            'item_count' => $cartService->getCount(),
            'total' => $summary['subtotal'] - $summary['discount'],
            'discount_amount' => $summary['discount'],
        ]);
    }

    // =========================================================================
    // ACTIONS — ADDRESS
    // =========================================================================

    public function openAddressPicker(): void
    {
        $this->showAddressPickerModal = true;
    }

    public function closeAddressPicker(): void
    {
        $this->showAddressPickerModal = false;
    }

    public function selectAddress(int $id): void
    {
        $this->selectedAddressId = $id;
        app(CheckoutSession::class)->setAddressId($id);

        // Reset shipping when address changes
        $this->selectedMethod = '';
        $this->selectedStationId = null;
        app(CheckoutSession::class)->clearShipping();

        $this->showAddressPickerModal = false;
        $this->dispatch('notify', variant: 'success', message: 'Delivery address updated');
        $this->dispatch('shipping-updated')->to('order-summary');
    }

    public function startCreateAddress(): void
    {
        $this->form->reset();
        $this->isEditingAddress = false;
        $this->showAddressPickerModal = false;
        $this->showAddressFormModal = true;
        $this->dispatch('address-modal-opened');
    }

    public function startEditAddress(int $id): void
    {
        $address = Address::findOrFail($id);
        $this->form->setAddress($address);
        $this->isEditingAddress = true;
        $this->showAddressPickerModal = false;
        $this->showAddressFormModal = true;
        $this->dispatch('address-modal-opened');
    }

    public function saveAddress(): void
    {
        try {
            $address = $this->isEditingAddress ? $this->form->update() : $this->form->store();

            $this->selectedAddressId = $address->id;
            app(CheckoutSession::class)->setAddressId($address->id);

            $this->showAddressFormModal = false;
            $this->dispatch('notify', variant: 'success', message: 'Address saved successfully');

            // Refresh shipping
            $this->selectedMethod = '';
            app(CheckoutSession::class)->clearShipping();
            $this->dispatch('shipping-updated')->to('order-summary');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to save address');
        }
    }

    public function closeAddressForm(): void
    {
        $this->showAddressFormModal = false;
    }

    // =========================================================================
    // ACTIONS — SHIPPING
    // =========================================================================

    public function openShippingModal(): void
    {
        $this->showShippingModal = true;
    }

    public function closeShippingModal(): void
    {
        $this->showShippingModal = false;
    }

    public function updatedSelectedMethod(): void
    {
        if ($this->selectedMethod !== $this->shippingOptions->firstWhere('methodType', 'pus')?->methodCode) {
            $this->selectedStationId = null;
        }
        $this->dispatch('shipping-updated')->to('order-summary');
    }

    public function confirmShipping(): void
    {
        if (!$this->selectedMethod) {
            return;
        }

        $option = $this->selectedOption->isPus() ? $this->currentPusOption : $this->selectedOption;
        $stationName = $this->selectedStationId ? $option->pickupStations?->firstWhere('id', $this->selectedStationId)?->name : null;

        app(CheckoutSession::class)->setShipping([
            'method_id' => $option->methodId,
            'method_name' => $option->methodName,
            'method_code' => $option->methodCode,
            'method_type' => $option->methodType,
            'cost' => $option->cost,
            'zone_id' => $option->shippingZoneId,
            'rate_id' => $option->shippingRateId,
            'station_id' => $this->selectedStationId,
            'station_name' => $stationName,
            'cost_breakdown' => $option->costBreakdown,
            'delivery_window' => $option->deliveryWindow(),
        ]);

        $this->showShippingModal = false;
        $this->dispatch('notify', variant: 'success', message: 'Shipping method updated');
        $this->dispatch('shipping-updated')->to('order-summary');
    }

    // =========================================================================
    // ACTIONS — PAYMENT
    // =========================================================================

    public function openPaymentModal(): void
    {
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    public function confirmPayment(): void
    {
        app(CheckoutSession::class)->setPaymentMethod($this->paymentMethod);
        $this->showPaymentModal = false;
        $this->dispatch('notify', variant: 'success', message: 'Payment method updated');
    }
}; ?>

<div>
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('cart') }}" wire:navigate>Cart</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Checkout</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Checkout</x-slot:heading>

    {{-- 1. DELIVERY ADDRESS --}}
    <div class="bg-white border border-zinc-200 mb-5">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
            <div class="flex items-center gap-3">
                <div @class([
                    'w-5.5 h-5.5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300',
                    $this->address ? 'bg-green-500 text-white' : 'bg-zinc-100 text-zinc-400',
                ])>
                    @if ($this->address)
                        <flux:icon.check class="size-3" />
                    @else
                        <span class="text-[11px] font-extrabold">1</span>
                    @endif
                </div>
                <span class="font-serif text-base font-extrabold uppercase tracking-[0.04em]">
                    Delivery <em class="text-primary not-italic">Address</em>
                </span>
            </div>
            <button type="button"
                wire:click="{{ $this->addresses->isEmpty() ? 'startCreateAddress' : 'openAddressPicker' }}"
                class="flex items-center gap-1 text-[11px] font-extrabold tracking-[0.08em] uppercase text-primary bg-transparent border-none cursor-pointer hover:opacity-70 transition-opacity">
                {{ $this->address ? 'Change' : 'Select' }}
                <flux:icon.chevron-right class="size-3.5 stroke-2" />
            </button>
        </div>
        <div class="p-5">
            @if ($this->address)
                <span
                    class="inline-block text-[9px] font-black uppercase tracking-widest px-2 py-0.5 bg-secondary text-white mb-2">{{ $this->address->label ?? 'Home' }}</span>
                <div class="text-[15px] font-semibold text-zinc-950 mb-1">{{ $this->address->full_name }}</div>
                <div class="text-[13px] text-zinc-500 font-medium leading-relaxed">
                    {!! nl2br(e($this->address->address)) !!}<br>
                    {{ implode(', ', array_filter([$this->address->area?->name, $this->address->county?->name])) }}<br>
                    {{ format_phone($this->address->phone_number) }}
                </div>
            @else
                <div class="">
                    <div class="bg-secondary text-white flex items-center px-1.5 py-[0.5px] mb-2 w-fit">
                        <flux:icon.minus class="size-5" />
                    </div>

                    <p class="font-semibold tracking-tight mb-1">No address saved</p>
                    <p class="text-zinc-400 text-[13px] tracking-tight">Add a delivery address
                        to continue.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- 2. SHIPPING METHOD --}}
    <div @class([
        'bg-white border border-zinc-200 mb-5',
        'opacity-50 pointer-events-none' => !$this->address,
    ])>
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
            <div class="flex items-center gap-3">
                <div @class([
                    'w-5.5 h-5.5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300',
                    $this->shipping ? 'bg-green-500 text-white' : 'bg-zinc-100 text-zinc-400',
                ])>
                    @if ($this->shipping)
                        <flux:icon.check class="size-3" />
                    @else
                        <span class="text-[11px] font-extrabold">2</span>
                    @endif
                </div>
                <span class="font-serif text-base font-extrabold uppercase tracking-[0.04em]">
                    Shipping <em class="text-primary not-italic">Method</em>
                </span>
            </div>
            <button type="button" wire:click="openShippingModal"
                class="flex items-center gap-1 text-[11px] font-extrabold tracking-[0.08em] uppercase text-primary bg-transparent border-none cursor-pointer hover:opacity-70 transition-opacity">
                {{ $this->shipping ? 'Change' : 'Select' }}
                <flux:icon.chevron-right class="size-3.5 stroke-2" />
            </button>
        </div>
        <div class="p-5">
            @if ($this->shipping)
                <div class="flex items-center justify-between">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-10 h-10 bg-zinc-50 flex items-center justify-center shrink-0 border border-zinc-200 rounded-sm">
                            <flux:icon.truck class="size-5 text-zinc-950" />
                        </div>
                        <div>
                            <div class="text-[14px] font-bold text-zinc-950 mb-0.5">
                                {{ $this->shipping['method_name'] }}</div>
                            <div class="text-[12px] text-zinc-500 leading-tight font-medium">
                                {{ $this->shipping['delivery_window'] }} · Door Delivery
                                @if ($this->shipping['station_name'])
                                    · Pickup: {{ $this->shipping['station_name'] }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div @class([
                        'text-[15px] font-bold font-sans',
                        'text-green-500' => $this->shipping['cost'] == 0,
                    ])>
                        {{ $this->shipping['cost'] == 0 ? 'FREE' : format_currency($this->shipping['cost']) }}
                    </div>
                </div>
            @else
                <p class="text-zinc-400 text-[13px] font-bold uppercase tracking-tight italic">Please select an
                    address first</p>
            @endif
        </div>
    </div>

    {{-- 3. PAYMENT METHOD --}}
    <div @class([
        'bg-white border border-zinc-200 mb-8',
        'opacity-50 pointer-events-none' => !$this->shipping,
    ])>
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
            <div class="flex items-center gap-3">
                <div @class([
                    'w-5.5 h-5.5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300',
                    $this->paymentMethod
                        ? 'bg-green-500 text-white'
                        : 'bg-zinc-100 text-zinc-400',
                ])>
                    @if ($this->paymentMethod)
                        <flux:icon.check class="size-3" />
                    @else
                        <span class="text-[11px] font-extrabold">3</span>
                    @endif
                </div>
                <span class="font-serif text-base font-extrabold uppercase tracking-[0.04em]">
                    Payment <em class="text-primary not-italic">Method</em>
                </span>
            </div>
            <button type="button" wire:click="openPaymentModal"
                class="flex items-center gap-1 text-[11px] font-extrabold tracking-[0.08em] uppercase text-primary bg-transparent border-none cursor-pointer hover:opacity-70 transition-opacity">
                Change
                <flux:icon.chevron-right class="size-3.5 stroke-2" />
            </button>
        </div>
        <div class="p-5 md:px-6">
            @php
                $pmName = match ($paymentMethod) {
                    'card' => 'Credit / Debit Card',
                    'mpesa' => 'M-Pesa Mobile Money',
                    default => 'Standard Gateway',
                };
                $pmIcon = match ($paymentMethod) {
                    'card' => 'CARD',
                    'mpesa' => 'M-PESA',
                    default => 'PAY',
                };
            @endphp
            <div class="flex items-center gap-4">
                <div
                    class="w-14 h-9 bg-white border-1.5 border-zinc-200 flex items-center justify-center text-[10px] font-black tracking-widest shrink-0 rounded-sm">
                    {{ $pmIcon }}
                </div>
                <div>
                    <div class="text-[14px] font-bold text-zinc-950 mb-0.5">{{ $pmName }}</div>
                    <div class="text-[12px] text-zinc-500 font-medium tracking-tight">
                        {{ $paymentMethod === 'card' ? 'Visa, Mastercard, Amex accepted' : ($paymentMethod === 'mpesa' ? 'STK push to your Safaricom line' : 'Secure payment processing') }}
                    </div>
                </div>
            </div>
            <div class="flex gap-2 mt-4 pt-4 border-t border-zinc-100">
                <span @class([
                    'text-[9px] font-black px-2 py-1 border',
                    $paymentMethod === 'card'
                        ? 'bg-secondary border-secondary text-white'
                        : 'bg-zinc-50 border-zinc-200 text-zinc-400',
                ])>VISA</span>
                <span @class([
                    'text-[9px] font-black px-2 py-1 border',
                    $paymentMethod === 'card'
                        ? 'bg-secondary border-secondary text-white'
                        : 'bg-zinc-50 border-zinc-200 text-zinc-400',
                ])>MASTERCARD</span>
                <span @class([
                    'text-[9px] font-black px-2 py-1 border',
                    $paymentMethod === 'mpesa'
                        ? 'bg-secondary border-secondary text-white'
                        : 'bg-zinc-50 border-zinc-200 text-zinc-400',
                ])>MPESA</span>
            </div>
        </div>
    </div>

    <a href="{{ route('cart') }}" wire:navigate
        class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-widest text-primary border-b-2 border-primary pb-0.5 hover:text-primary/90 hover:border-primary/90 transition-colors mb-10">
        <flux:icon.chevron-left class="size-3 stroke-2" />
        Back to Cart
    </a>

    <x-slot name="orderSummaryCta">
        <button @click="$dispatch('place-order')" x-data="{ processing: false }" @place-order.window="processing = true"
            :disabled="processing"
            class="w-full bg-primary text-white border-none py-4 px-6 font-sans text-[17px] font-bold uppercase tracking-widest cursor-pointer hover:bg-primary-dark transition-all disabled:opacity-50 disabled:cursor-not-allowed group">
            <div class="flex items-center justify-center gap-2 group-active:scale-95 transition-transform">
                <span x-show="!processing">Place Order →</span>
                <span x-show="processing" class="flex items-center gap-2" x-cloak>
                    <span>Processing...</span>
                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                </span>
            </div>
        </button>
    </x-slot>

    {{-- MOBILE STICKY CTA BAR --}}
    <div
        class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-between px-5 py-3.5 bg-white border-t-2 border-zinc-950 md:hidden">
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Total</div>
            <div class="font-sans font-bold text-[20px] text-primary leading-none">
                {{ format_currency($this->cartSummary['total'] + ($this->shipping['cost'] ?? 0)) }}
            </div>
        </div>
        <button @click="$dispatch('place-order')" x-data="{ processing: false }" @place-order.window="processing = true"
            :disabled="!@js($this->address) || !@js($this->shipping) || processing"
            class="bg-primary text-white font-sans text-[14px] font-bold uppercase tracking-widest px-5 py-2.5 hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer flex items-center gap-2">
            <span x-show="!processing">Place Order →</span>
            <span x-show="processing" class="flex items-center gap-2" x-cloak>
                <span>Processing...</span>
                <div class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            </span>
        </button>
    </div>

    {{-- ══════════════════════════════════
         MODALS
    ══════════════════════════════════ --}}

    {{-- 1. ADDRESS PICKER --}}
    @if ($this->showAddressPickerModal)
        <x-ui.modal wire:key="address-picker-modal" title="SELECT <em class='text-primary not-italic'>ADDRESS</em>"
            max-width="560px" wire:click.self="closeAddressPicker">
            <x-slot:close>
                <button wire:click="closeAddressPicker" type="button"
                    class="text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer">
                    <flux:icon.x-mark class="w-5 h-5" />
                </button>
            </x-slot:close>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="space-y-2.5">
                    @foreach ($this->addresses as $addr)
                        <div wire:key="addr-{{ $addr->id }}" wire:click="selectAddress({{ $addr->id }})"
                            @class([
                                'border-[1.5px] border-zinc-200 px-[18px] py-3.5 cursor-pointer transition-all relative flex items-start justify-between hover:border-zinc-950',
                                $selectedAddressId === $addr->id
                                    ? 'border-primary bg-[#fff8f6] before:content-[\'\'] before:absolute before:left-0 before:top-0 before:bottom-0 before:w-[3px] before:bg-primary'
                                    : '',
                            ])>
                            <div class="flex items-center gap-3.5 flex-1">
                                <div @class([
                                    'w-4 h-4 rounded-full border-2 shrink-0 flex items-center justify-center',
                                    $selectedAddressId === $addr->id
                                        ? 'border-primary after:content-[\'\'] after:w-2 after:h-2 after:bg-primary after:rounded-full'
                                        : 'border-zinc-300',
                                ])></div>
                                <div class="flex-1">
                                    <span
                                        class="inline-block text-[9px] font-extrabold uppercase tracking-widest px-2 py-0.5 bg-zinc-950 text-white mb-2">{{ $addr->label ?? 'Home' }}</span>
                                    <div class="text-[13px] font-semibold text-zinc-950 mb-0.5">{{ $addr->full_name }}
                                    </div>
                                    <div class="text-[11px] text-zinc-500 leading-relaxed font-medium">
                                        {{ $addr->address }}, {{ $addr->area?->name }},
                                        {{ $addr->county?->name }}
                                    </div>
                                </div>
                            </div>
                            <button wire:click.stop="startEditAddress({{ $addr->id }})"
                                class="text-zinc-300 hover:text-primary transition-colors cursor-pointer">
                                <flux:icon.pencil-square class="size-4" />
                            </button>
                        </div>
                    @endforeach

                    <button wire:click="startCreateAddress"
                        class="w-full border-[1.5px] border-dashed border-zinc-200 p-6 flex items-center justify-center gap-3 text-[12px] font-bold uppercase tracking-widest text-zinc-400 hover:border-primary hover:text-primary transition-all cursor-pointer group">
                        <flux:icon.plus class="size-[18px] text-zinc-300 group-hover:text-primary transition-colors" />
                        Add New Address
                    </button>
                </div>
            </div>

            <x-slot:footer>
                <div class="px-6 py-4 border-t border-zinc-200 flex gap-2.5 justify-end">
                    <button type="button"
                        class="bg-white text-zinc-950 text-[13px] font-extrabold font-serif uppercase tracking-widest px-5 py-2.5 border-[1.5px] border-zinc-950 hover:bg-zinc-950 hover:text-white transition-colors"
                        wire:click="closeAddressPicker">Cancel</button>
                    <button type="button"
                        class="bg-primary text-white text-[13px] font-extrabold font-serif uppercase tracking-widest px-6 py-2.5 hover:bg-[#e03d00] transition-colors"
                        wire:click="closeAddressPicker">Confirm Selection</button>
                </div>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    {{-- 2. ADDRESS FORM (Create/Edit) --}}
    @if ($this->showAddressFormModal)
        <x-ui.modal wire:key="address-form-modal"
            title="{{ $isEditingAddress ? 'EDIT' : 'NEW' }} <em class='text-primary not-italic'>ADDRESS</em>"
            max-width="640px" wire:click.self="closeAddressForm">
            <x-slot:close>
                <button wire:click="closeAddressForm" type="button"
                    class="text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer">
                    <flux:icon.x-mark class="w-5 h-5" />
                </button>
            </x-slot:close>

            <form wire:submit="saveAddress">
                @include('pages.customer.address-book._form-fields', [
                    'submitLabel' => $isEditingAddress ? 'Update Address' : 'Save Address',
                ])
            </form>
        </x-ui.modal>
    @endif

    {{-- 3. SHIPPING PICKER --}}
    @if ($this->showShippingModal)
        <x-ui.modal wire:key="shipping-modal" title="SELECT <em class='text-primary not-italic'>SHIPPING</em>"
            max-width="560px" wire:click.self="closeShippingModal">
            <x-slot:close>
                <button wire:click="closeShippingModal" type="button"
                    class="text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer">
                    <flux:icon.x-mark class="w-5 h-5" />
                </button>
            </x-slot:close>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="space-y-2.5">
                    @foreach ($this->shippingOptions as $option)
                        @php $isSelected = $selectedMethod === $option->methodCode; @endphp
                        <div wire:key="ship-{{ $option->methodCode }}"
                            wire:click="$set('selectedMethod', '{{ $option->methodCode }}')"
                            @class([
                                'border-[1.5px] border-zinc-200 px-4 py-3.5 cursor-pointer transition-all relative flex items-center justify-between hover:border-zinc-950',
                                $isSelected
                                    ? 'border-primary bg-[#fff8f6] before:content-[\'\'] before:absolute before:left-0 before:top-0 before:bottom-0 before:w-[3px] before:bg-primary'
                                    : '',
                            ])>
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-4 h-4 rounded-full border-2 shrink-0 flex items-center justify-center',
                                    $isSelected
                                        ? 'border-primary after:content-[\'\'] after:w-2 after:h-2 after:bg-primary after:rounded-full'
                                        : 'border-zinc-300',
                                ])></div>
                                <div>
                                    <div class="text-[13px] font-semibold text-zinc-950">{{ $option->methodName }}
                                    </div>
                                    <div class="text-[11px] text-zinc-500 font-medium mt-0.5">
                                        {{ $option->deliveryWindow() }}</div>
                                </div>
                            </div>
                            <div @class([
                                'text-[13px] font-bold',
                                $option->isFree() ? 'text-green-500' : 'text-zinc-950',
                            ])>
                                {{ $option->isFree() ? 'FREE' : $option->formattedCost() }}
                            </div>
                        </div>

                        @if ($option->isPus() && $isSelected && $option->pickupStations?->isNotEmpty())
                            <div class="mb-2 mt-[-8px] p-4 bg-zinc-50 border-x-[1.5px] border-b-[1.5px] border-primary"
                                wire:click.stop>
                                <label
                                    class="text-[11px] font-bold uppercase tracking-widest text-zinc-950 mb-2 block">Choose
                                    Pickup Station</label>
                                <select wire:model.live="selectedStationId"
                                    class="w-full border-[1.5px] border-zinc-200 p-2.5 text-[13px] font-medium outline-none focus:border-primary appearance-none bg-[url('data:image/svg+xml,%3Csvg_xmlns=%22http://www.w3.org/2000/svg%22_width=%2210%22_height=%226%22%3E%3Cpath_d=%22M0_0l5_6_5-6z%22_fill=%22%2318181b%22/%3E%3C/svg%3E')] bg-no-repeat bg-[right_12px_center]">
                                    <option value="">Choose a station...</option>
                                    @foreach ($option->pickupStations as $station)
                                        <option value="{{ $station->id }}">{{ $station->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <x-slot:footer>
                <div class="px-6 py-4 border-t border-zinc-200 flex gap-2.5 justify-end">
                    <button type="button"
                        class="bg-white text-zinc-950 text-[13px] font-extrabold font-serif uppercase tracking-widest px-5 py-2.5 border-[1.5px] border-zinc-950 hover:bg-zinc-950 hover:text-white transition-colors"
                        wire:click="closeShippingModal">Cancel</button>
                    <button type="button" wire:click="confirmShipping" :disabled="!@js($selectedMethod)"
                        class="bg-primary text-white text-[13px] font-extrabold font-serif uppercase tracking-widest px-6 py-2.5 hover:bg-[#e03d00] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Confirm
                        Method</button>
                </div>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    {{-- 4. PAYMENT PICKER --}}
    @if ($this->showPaymentModal)
        <x-ui.modal wire:key="payment-modal" title="SELECT <em class='text-primary not-italic'>PAYMENT</em>"
            max-width="560px" wire:click.self="closePaymentModal">
            <x-slot:close>
                <button wire:click="closePaymentModal" type="button"
                    class="text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer">
                    <flux:icon.x-mark class="w-5 h-5" />
                </button>
            </x-slot:close>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="space-y-2.5">
                    @foreach (['mpesa' => ['name' => 'M-Pesa Mobile Money', 'sub' => 'Secure STK Push to your phone', 'icon' => 'M-PESA'], 'card' => ['name' => 'Credit / Debit Card', 'sub' => 'Visa, Mastercard, Amex secured', 'icon' => 'CARD']] as $key => $data)
                        @php $isSelected = $paymentMethod === $key; @endphp
                        <div wire:key="pay-{{ $key }}"
                            wire:click="$set('paymentMethod', '{{ $key }}')" @class([
                                'border-[1.5px] border-zinc-200 px-4 py-3.5 cursor-pointer transition-all relative flex items-center gap-3.5 hover:border-zinc-950',
                                $isSelected
                                    ? 'border-primary bg-[#fff8f6] before:content-[\'\'] before:absolute before:left-0 before:top-0 before:bottom-0 before:w-[3px] before:bg-primary'
                                    : '',
                            ])>
                            <div @class([
                                'w-4 h-4 rounded-full border-2 shrink-0 flex items-center justify-center',
                                $isSelected
                                    ? 'border-primary after:content-[\'\'] after:w-2 after:h-2 after:bg-primary after:rounded-full'
                                    : 'border-zinc-300',
                            ])></div>
                            <div
                                class="text-[11px] font-extrabold py-1 px-2.5 border-[1.5px] border-zinc-200 tracking-[0.04em] min-w-[52px] text-center font-serif shrink-0">
                                {{ $data['icon'] }}
                            </div>
                            <div>
                                <div class="text-[13px] font-semibold text-zinc-950">{{ $data['name'] }}</div>
                                <div class="text-[11px] text-zinc-500 font-medium mt-0.5">
                                    {{ $data['sub'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-slot:footer>
                <div class="px-6 py-4 border-t border-zinc-200 flex gap-2.5 justify-end">
                    <button type="button"
                        class="bg-white text-zinc-950 text-[13px] font-extrabold font-serif uppercase tracking-widest px-5 py-2.5 border-[1.5px] border-zinc-950 hover:bg-zinc-950 hover:text-white transition-colors"
                        wire:click="closePaymentModal">Cancel</button>
                    <button type="button" wire:click="confirmPayment"
                        class="bg-primary text-white text-[13px] font-extrabold font-serif uppercase tracking-widest px-6 py-2.5 hover:bg-[#e03d00] transition-colors">Use
                        This Method
                    </button>
                </div>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
