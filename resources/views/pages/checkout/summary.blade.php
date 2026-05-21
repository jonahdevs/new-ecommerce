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
    public string $paymentMethod = 'card';

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
        } else {
            $checkoutSession->setPaymentMethod($this->paymentMethod);
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
        $this->dispatch('notify', title: 'Address Updated', variant: 'success', message: 'Delivery address updated');
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
            $this->dispatch('notify', title: 'Address Saved', variant: 'success', message: 'Address saved successfully');

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
        $this->dispatch('notify', title: 'Shipping Updated', variant: 'success', message: 'Shipping method updated');
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
        $this->dispatch('notify', title: 'Payment Updated', variant: 'success', message: 'Payment method updated');
    }

    // =========================================================================
    // ACTIONS — PLACE ORDER
    // =========================================================================

    #[On('place-order')]
    public function placeOrder()
    {
        try {
            // Validate that all required steps are completed
            if (!$this->address) {
                $this->dispatch('notify', title: 'Address Required', variant: 'danger', message: 'Please select a delivery address');
                return;
            }

            if (!$this->shipping) {
                $this->dispatch('notify', title: 'Shipping Required', variant: 'danger', message: 'Please select a shipping method');
                return;
            }

            // Dispatch to the order summary component to handle the actual order placement
            $this->dispatch('complete-order')->to('order-summary');
        } catch (\Exception $e) {
            $this->dispatch('notify', title: 'Order Failed', variant: 'danger', message: 'Unable to place order. Please try again.');
        }
    }

    #[Computed]
    public function paymentDisplay(): array
    {
        return match ($this->paymentMethod) {
            'card' => ['name' => 'Credit / Debit Card', 'icon' => 'CARD'],
            'mpesa' => ['name' => 'M-Pesa Mobile Money', 'icon' => 'M-PESA'],
            default => ['name' => 'Standard Gateway', 'icon' => 'PAY'],
        };
    }
}; ?>

<div>
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('cart') }}" wire:navigate>Cart</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Checkout</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Checkout</x-slot:heading>

    {{-- 1. DELIVERY ADDRESS --}}
    <x-customer.card class="mb-5" title="Delivery" titleEm="Address">
        <x-slot:icon>
            <div @class([
                'w-5.5 h-5.5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300',
                $this->address
                    ? 'bg-green-500 text-white'
                    : 'bg-zinc-100 text-on-surface-variant',
            ])>
                @if ($this->address)
                    <flux:icon.check class="size-3" />
                @else
                    <span class="text-[11px] font-extrabold">1</span>
                @endif
            </div>
        </x-slot:icon>
        <x-slot:action>
            <button type="button"
                wire:click="{{ $this->addresses->isEmpty() ? 'startCreateAddress' : 'openAddressPicker' }}"
                class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-primary hover:opacity-70 transition-opacity cursor-pointer bg-transparent border-none">
                {{ $this->address ? 'Change' : 'Select' }}
                <flux:icon.chevron-right class="w-3.5 h-3.5 stroke-2" />
            </button>
        </x-slot:action>

        @if ($this->address)
            <span
                class="inline-block text-[9px] font-extrabold uppercase tracking-widest px-2 py-0.5 bg-secondary text-white mb-2">{{ $this->address->label ?? 'Home' }}</span>
            <div class="text-[14px] font-bold text-on-surface mb-1">{{ $this->address->full_name }}</div>
            <div class="text-[12px] text-on-surface-variant font-medium leading-[1.7]">
                {!! nl2br(e($this->address->address)) !!}<br>
                {{ implode(', ', array_filter([$this->address->area?->name, $this->address->county?->name])) }}<br>
                {{ format_phone($this->address->phone_number) }}
            </div>
        @else
            <div class="">
                <div class="bg-secondary text-white flex items-center px-1.5 py-[0.5px] mb-2 w-fit">
                    <flux:icon.minus class="size-5" />
                </div>

                <p class="text-[13px] font-bold text-on-surface mb-1">No address saved</p>
                <p class="text-[12px] text-on-surface-variant font-medium">Add a delivery address to continue.</p>
            </div>
        @endif
    </x-customer.card>

    {{-- 2. SHIPPING METHOD --}}
    <x-customer.card @class(['mb-5', 'opacity-50 pointer-events-none' => !$this->address]) title="Shipping" titleEm="Method">
        <x-slot:icon>
            <div @class([
                'w-5.5 h-5.5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300',
                $this->shipping
                    ? 'bg-green-500 text-white'
                    : 'bg-zinc-100 text-on-surface-variant',
            ])>
                @if ($this->shipping)
                    <flux:icon.check class="size-3" />
                @else
                    <span class="text-[11px] font-extrabold">2</span>
                @endif
            </div>
        </x-slot:icon>
        <x-slot:action>
            <button type="button" wire:click="openShippingModal"
                class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-primary hover:opacity-70 transition-opacity cursor-pointer bg-transparent border-none">
                {{ $this->shipping ? 'Change' : 'Select' }}
                <flux:icon.chevron-right class="w-3.5 h-3.5 stroke-2" />
            </button>
        </x-slot:action>

        @if ($this->shipping)
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 bg-zinc-50 flex items-center justify-center shrink-0 border border-zinc-200 rounded-sm">
                        <flux:icon.truck class="size-5 text-on-surface" />
                    </div>
                    <div>
                        <div class="text-[13px] font-bold text-on-surface mb-0.5">
                            {{ $this->shipping['method_name'] }}</div>
                        <div class="text-[11px] text-on-surface-variant leading-tight font-medium">
                            {{ $this->shipping['delivery_window'] }} · Door Delivery
                            @if ($this->shipping['station_name'])
                                · Pickup: {{ $this->shipping['station_name'] }}
                            @endif
                        </div>
                    </div>
                </div>
                <div @class([
                    'text-[14px] font-bold',
                    'text-green-600' => $this->shipping['cost'] == 0,
                    'text-on-surface' => $this->shipping['cost'] > 0,
                ])>
                    {{ $this->shipping['cost'] == 0 ? 'FREE' : format_currency($this->shipping['cost']) }}
                </div>
            </div>
        @else
            <p class="text-[11px] text-on-surface-variant font-bold uppercase tracking-wider">
                {{ $this->address ? 'Please select a shipping method' : 'Please select an address first' }}
            </p>
        @endif
    </x-customer.card>

    {{-- 3. PAYMENT METHOD --}}
    <x-customer.card @class(['mb-8', 'opacity-50 pointer-events-none' => !$this->shipping]) title="Payment" titleEm="Method">
        <x-slot:icon>
            <div @class([
                'w-5.5 h-5.5 rounded-full flex items-center justify-center shrink-0 transition-all duration-300',
                $this->paymentMethod
                    ? 'bg-green-500 text-white'
                    : 'bg-zinc-100 text-on-surface-variant',
            ])>
                @if ($this->paymentMethod)
                    <flux:icon.check class="size-3" />
                @else
                    <span class="text-[11px] font-extrabold">3</span>
                @endif
            </div>
        </x-slot:icon>
        <x-slot:action>
            <button type="button" wire:click="openPaymentModal"
                class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-primary hover:opacity-70 transition-opacity cursor-pointer bg-transparent border-none">
                Change
                <flux:icon.chevron-right class="w-3.5 h-3.5 stroke-2" />
            </button>
        </x-slot:action>


        <div class="flex items-center gap-4">
            <div
                class="w-14 h-9 bg-white border-1.5 border-zinc-200 flex items-center justify-center text-[10px] font-extrabold tracking-widest shrink-0 rounded-sm">
                {{ $this->paymentDisplay['icon'] }}
            </div>
            <div>
                <div class="text-[13px] font-bold text-on-surface mb-0.5">{{ $this->paymentDisplay['name'] }}</div>
                <div class="text-[11px] text-on-surface-variant font-medium">
                    {{ $paymentMethod === 'card' ? 'Visa, Mastercard, Amex accepted' : ($paymentMethod === 'mpesa' ? 'STK push to your Safaricom line' : 'Secure payment processing') }}
                </div>
            </div>
        </div>
        <div class="flex gap-2 mt-4 pt-4 border-t border-zinc-100">
            <span @class([
                'text-[9px] font-extrabold px-2 py-1 border',
                $paymentMethod === 'card'
                    ? 'bg-secondary border-secondary text-white'
                    : 'bg-zinc-50 border-zinc-200 text-on-surface-variant',
            ])>VISA</span>
            <span @class([
                'text-[9px] font-extrabold px-2 py-1 border',
                $paymentMethod === 'card'
                    ? 'bg-secondary border-secondary text-white'
                    : 'bg-zinc-50 border-zinc-200 text-on-surface-variant',
            ])>MASTERCARD</span>
            <span @class([
                'text-[9px] font-extrabold px-2 py-1 border',
                $paymentMethod === 'mpesa'
                    ? 'bg-secondary border-secondary text-white'
                    : 'bg-zinc-50 border-zinc-200 text-on-surface-variant',
            ])>MPESA</span>
        </div>
    </x-customer.card>

    <a href="{{ route('cart') }}" wire:navigate
        class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-widest text-primary border-b-2 border-primary pb-0.5 hover:text-primary/90 hover:border-primary/90 transition-colors mb-10">
        <flux:icon.chevron-left class="size-3 stroke-2" />
        Back to Cart
    </a>

    <x-slot name="orderSummaryCta">
        <div x-data="{
            processing: false,
            get isDisabled() {
                return !{{ $this->address ? 'true' : 'false' }} || !{{ $this->shipping ? 'true' : 'false' }} || !{{ $this->paymentMethod ? 'true' : 'false' }} || this.processing;
            }
        }">
            <flux:button @click="!isDisabled && $dispatch('place-order')" @place-order.window="processing = true"
                ::disabled="isDisabled" class="w-full group cursor-pointer" variant="customer-primary"
                size="customer-lg">
                <div class="flex items-center justify-center gap-2 group-active:scale-95 transition-transform">
                    <span x-show="!processing" class="inline-flex items-center gap-3 group">Place Order
                        <flux:icon.chevron-right class="size-3.5 group-hover:translate-x-1 transition-transform" />
                    </span>
                    <span x-show="processing" class="flex items-center gap-2" x-cloak>
                        <span>Processing</span>
                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    </span>
                </div>
            </flux:button>
        </div>

        <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-on-surface-variant ">
            <flux:icon.shield-check class="size-3" />
            <span class="uppercase tracking-widest">SSL Encrypted & Secure</span>
        </div>
    </x-slot>

    {{-- MOBILE STICKY CTA BAR --}}
    <div
        class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-between px-5 py-3.5 bg-white border-t-2 border-zinc-950 md:hidden">
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total</div>
            <div class="font-sans font-bold text-[20px] text-primary leading-none">
                {{ format_currency($this->cartSummary['total'] + ($this->shipping['cost'] ?? 0)) }}
            </div>
        </div>
        <button @click="!isDisabled && $dispatch('place-order')" x-data="{
            processing: false,
            get isDisabled() {
                return !{{ $this->address ? 'true' : 'false' }} || !{{ $this->shipping ? 'true' : 'false' }} || !{{ $this->paymentMethod ? 'true' : 'false' }} || this.processing;
            }
        }"
            @place-order.window="processing = true" :disabled="isDisabled"
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
                    class="text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer group">
                    <flux:icon.x-mark class="w-5 h-5 group-hover:rotate-90 duration-150 ease-in-out" />
                </button>
            </x-slot:close>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="space-y-2.5">
                    @foreach ($this->addresses as $addr)
                        <div wire:key="addr-{{ $addr->id }}" wire:click="selectAddress({{ $addr->id }})"
                            @class([
                                'border-[1.5px] border-zinc-200 px-[18px] py-3.5 cursor-pointer transition-all relative flex items-start justify-between hover:border-secondary',
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
                                    <div class="text-[13px] font-bold text-on-surface mb-0.5">{{ $addr->full_name }}
                                    </div>
                                    <div class="text-[11px] text-on-surface-variant leading-[1.7] font-medium">
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
                        class="w-full border-[1.5px] border-dashed border-zinc-200 p-6 flex items-center justify-center gap-3 text-[12px] font-bold uppercase tracking-widest text-on-surface-variant hover:border-primary hover:text-primary transition-all cursor-pointer group">
                        <flux:icon.plus class="size-4.5 text-zinc-300 group-hover:text-primary transition-colors" />
                        Add New Address
                    </button>
                </div>
            </div>

            <x-slot:footer>
                <div class="px-6 py-4 border-t border-zinc-200 flex gap-2.5 justify-end">
                    <flux:button type="button" variant="customer-outline" size="customer"
                        class="cursor-pointer px-5!" wire:click="closeAddressPicker">
                        Cancel
                    </flux:button>
                    <flux:button type="button" variant="customer-primary" size="customer"
                        class="cursor-pointer px-6!" wire:click="closeAddressPicker">
                        Confirm Selection
                    </flux:button>
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
                    class="text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer group">
                    <flux:icon.x-mark class="w-5 h-5 group-hover:rotate-90 duration-150 ease-in-out" />
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
                    class="text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer group">
                    <flux:icon.x-mark class="w-5 h-5 group-hover:rotate-90 duration-150 ease-in-out" />
                </button>
            </x-slot:close>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="space-y-2.5">
                    @foreach ($this->shippingOptions as $option)
                        @php $isSelected = $selectedMethod === $option->methodCode; @endphp
                        <div wire:key="ship-{{ $option->methodCode }}"
                            wire:click="$set('selectedMethod', '{{ $option->methodCode }}')"
                            @class([
                                'border-[1.5px] border-zinc-200 px-4 py-3.5 cursor-pointer transition-all relative flex items-center justify-between hover:border-secondary',
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
                                    <div class="text-[13px] font-bold text-on-surface">{{ $option->methodName }}
                                    </div>
                                    <div class="text-[11px] text-on-surface-variant font-medium mt-0.5">
                                        {{ $option->deliveryWindow() }}</div>
                                </div>
                            </div>
                            <div @class([
                                'text-[14px] font-bold',
                                $option->isFree() ? 'text-green-600' : 'text-on-surface',
                            ])>
                                {{ $option->isFree() ? 'FREE' : $option->formattedCost() }}
                            </div>
                        </div>

                        @if ($option->isPus() && $isSelected && $option->pickupStations?->isNotEmpty())
                            <div class="mb-2 -mt-2 p-4 bg-zinc-50 border-x-[1.5px] border-b-[1.5px] border-primary"
                                wire:click.stop>
                                <x-customer.form-field label="Choose Pickup Station">
                                    <select wire:model.live="selectedStationId"
                                        class="customer-input appearance-none bg-[url('data:image/svg+xml,%3Csvg_xmlns=%22http://www.w3.org/2000/svg%22_width=%2210%22_height=%226%22%3E%3Cpath_d=%22M0_0l5_6_5-6z%22_fill=%22%2318181b%22/%3E%3C/svg%3E')] bg-no-repeat bg-[right_12px_center]">
                                        <option value="">Choose a station...</option>
                                        @foreach ($option->pickupStations as $station)
                                            <option value="{{ $station->id }}">{{ $station->name }}</option>
                                        @endforeach
                                    </select>
                                </x-customer.form-field>
                            </div>
                        @endif
                    @endforeach
                </div>
                <x-slot:footer>
                    <div class="px-6 py-4 border-t border-zinc-200 flex gap-2.5 justify-end">
                        <flux:button type="button" variant="customer-outline" size="customer"
                            class="cursor-pointer px-5!" wire:click="closeShippingModal">
                            Cancel
                        </flux:button>

                        @if ($selectedMethod)
                            <flux:button type="button" wire:click="confirmShipping" variant="customer-primary"
                                size="customer" class="cursor-pointer px-6!">
                                Confirm Method
                            </flux:button>
                        @else
                            <flux:button type="button" disabled variant="customer-primary" size="customer"
                                class="cursor-pointer px-6! opacity-50">
                                Confirm Method
                            </flux:button>
                        @endif
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
                    class="text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer group">
                    <flux:icon.x-mark class="w-5 h-5 group-hover:rotate-90 duration-150 ease-in-out" />
                </button>
            </x-slot:close>

            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="space-y-2.5">
                    @foreach (['mpesa' => ['name' => 'M-Pesa Mobile Money', 'sub' => 'Secure STK Push to your phone', 'icon' => 'M-PESA'], 'card' => ['name' => 'Credit / Debit Card', 'sub' => 'Visa, Mastercard, Amex secured', 'icon' => 'CARD']] as $key => $data)
                        @php $isSelected = $paymentMethod === $key; @endphp
                        <div wire:key="pay-{{ $key }}"
                            wire:click="$set('paymentMethod', '{{ $key }}')" @class([
                                'border-[1.5px] border-zinc-200 px-4 py-3.5 cursor-pointer transition-all relative flex items-center gap-3.5 hover:border-secondary',
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
                                <div class="text-[13px] font-bold text-on-surface">{{ $data['name'] }}</div>
                                <div class="text-[11px] text-on-surface-variant font-medium mt-0.5">
                                    {{ $data['sub'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-slot:footer>
                <div class="px-6 py-4 border-t border-zinc-200 flex gap-2.5 justify-end">
                    <flux:button type="button" variant="customer-outline" size="customer"
                        class="cursor-pointer px-5!" wire:click="closePaymentModal">
                        Cancel
                    </flux:button>
                    <flux:button type="button" wire:click="confirmPayment" variant="customer-primary"
                        size="customer" class="cursor-pointer px-6!">
                        Use This Method
                    </flux:button>
                </div>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
