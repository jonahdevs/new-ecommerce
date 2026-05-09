<?php

use App\Models\Address;
use App\Services\CartService;
use App\Services\CheckoutSession;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Shipping\ShippingOption;
use Livewire\Attributes\{Computed, Layout, Locked};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.checkout')] class extends Component {
    // The selected method code — bound to the radio group
    public string $selectedMethod = '';

    // PUS only — the chosen station ID
    public ?int $selectedStationId = null;

    #[Locked]
    public ?int $addressId = null;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $user = auth()->user();

        // Guard: no address at all
        if ($user->addresses()->doesntExist()) {
            $this->redirectRoute('checkout.addresses.create', navigate: true);
            return;
        }

        // Resolve which address to calculate for
        $checkoutSession = app(CheckoutSession::class);
        $sessionAddressId = $checkoutSession->getAddressId();

        $this->addressId = $sessionAddressId && $user->addresses()->where('id', $sessionAddressId)->exists() ? $sessionAddressId : $user->addresses()->where('is_default', true)->value('id') ?? $user->addresses()->oldest()->value('id');

        app(CheckoutSession::class)->setAddressId($this->addressId);

        // Guard: address exists but has no shipping zone resolved
        if (!$this->address?->shipping_zone_id) {
            $this->dispatch('notify', variant: 'danger', message: 'Your address is missing location details. Please update it.');
            $this->redirectRoute('customer.address-book.index', navigate: true);
            return;
        }

        // Pre-select the method if one is already in the session
        if ($checkoutSession->hasShipping()) {
            $this->selectedMethod = $checkoutSession->getShipping()['method_code'] ?? '';
            $this->selectedStationId = $checkoutSession->getPickupStationId();
        }
    }

    // Computed

    #[Computed]
    public function address(): ?Address
    {
        return $this->addressId ? Address::with(['county', 'area', 'shippingZone'])->find($this->addressId) : null;
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
    public function pusOption(): ?ShippingOption
    {
        return $this->shippingOptions->firstWhere('methodType', 'pus');
    }

    #[Computed]
    public function currentPusOption(): ?ShippingOption
    {
        if (!$this->selectedOption?->isPus()) {
            return null;
        }

        // Recalculate for the chosen station
        if ($this->selectedStationId) {
            return app(ShippingCalculator::class)->recalculateForStation(option: $this->selectedOption, stationId: $this->selectedStationId, weightKg: app(CartService::class)->getWeight());
        }

        return $this->selectedOption;
    }

    //  Actions

    public function updatedSelectedMethod(): void
    {
        // Clear station when switching away from PUS
        if ($this->selectedMethod !== $this->pusOption?->methodCode) {
            $this->selectedStationId = null;
        }

        $this->dispatch('shipping-updated')->to('order-summary');
    }

    public function confirm()
    {
        // Validate a method is selected
        if (!$this->selectedOption) {
            $this->dispatch('notify', variant: 'danger', message: 'Please select a shipping method.');
            return null;
        }

        // PUS requires a station
        if ($this->selectedOption->isPus() && !$this->selectedStationId) {
            $this->dispatch('notify', variant: 'danger', message: 'Please select a pickup station.');
            return null;
        }

        // Normal flow — build the option to store
        $option = $this->selectedOption->isPus() ? $this->currentPusOption : $this->selectedOption;

        $stationName = null;
        if ($this->selectedStationId) {
            $stationName = $option->pickupStations?->firstWhere('id', $this->selectedStationId)?->name;
        }

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

        $this->redirectRoute('checkout.summary', navigate: true);
    }

    public function updatedSelectedStationId(): void
    {
        $this->dispatch('shipping-updated')->to('order-summary');
    }
}; ?>

<div>
    {{-- Breadcrumb --}}
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>
                Checkout
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Shipping</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Shipping Method</x-slot:heading>

    {{-- Address card --}}
    <flux:card class="p-0 mb-4">
        <div class="px-4 py-2 border-b flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <flux:icon.check-circle variant="solid" class="size-5 text-green-500" />
                <flux:heading level="3" class="font-medium!">Delivering to</flux:heading>
            </div>
            <flux:link :href="route('checkout.addresses.index')" wire:navigate class="text-xs!">
                Change
                <flux:icon.chevron-right class="size-3.5 ms-1 inline-block" />
            </flux:link>
        </div>

        <div class="px-4 py-4">
            @if ($this->address)
                <flux:heading>{{ $this->address->full_name }}</flux:heading>
                <div class="mt-2 space-y-1">
                    <flux:text>{{ $this->address->address }}</flux:text>
                    <flux:text>
                        {{ implode(', ', array_filter([$this->address->area?->name, $this->address->county?->name])) }}
                    </flux:text>
                    <flux:text class="text-zinc-400 text-xs">
                        Zone: {{ $this->address->shippingZone?->name ?? '—' }}
                    </flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Shipping methods --}}
    <flux:card class="p-0 mb-4">
        <div class="px-4 py-2 border-b">
            <flux:heading level="3" class="font-medium!">Choose a shipping method</flux:heading>
        </div>

        <div class="p-4">
            @if ($this->shippingOptions->isEmpty())
                <div class="py-10 text-center">
                    <flux:icon.exclamation-triangle class="size-10 mx-auto text-zinc-400 mb-3" />
                    <flux:heading level="3" class="mb-1">No shipping options available</flux:heading>
                    <flux:text class="text-zinc-500 text-sm">
                        We couldn't find shipping options for your location. Please
                        <flux:link :href="route('customer.address-book.index')" wire:navigate>
                            update your address
                        </flux:link>
                        or contact support.
                    </flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($this->shippingOptions as $option)
                        @php $isSelected = $this->selectedMethod === $option->methodCode; @endphp

                        <label @class([
                            'flex items-start gap-4 p-4 border rounded-lg cursor-pointer transition-colors',
                            'border-zinc-800 bg-zinc-50 dark:border-zinc-300 dark:bg-zinc-800' => $isSelected,
                            'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' => !$isSelected,
                        ])>
                            <input type="radio" wire:model.live="selectedMethod" value="{{ $option->methodCode }}"
                                class="mt-1 accent-zinc-800" />

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-sm">{{ $option->methodName }}</span>
                                    <span @class([
                                        'text-sm font-semibold shrink-0',
                                        'text-green-600' => $option->isFree(),
                                        'text-zinc-800 dark:text-zinc-100' => !$option->isFree(),
                                    ])>
                                        {{ $option->formattedCost() }}
                                    </span>
                                </div>

                                <p class="text-xs text-zinc-500 mt-0.5">
                                    {{ $option->deliveryWindow() }}
                                    @if ($option->isPus())
                                        · Collect from a pickup station
                                    @endif
                                </p>

                                {{-- PUS station picker --}}
                                @if ($option->isPus() && $isSelected && $option->pickupStations?->isNotEmpty())
                                    <div class="mt-3 pt-3 border-t border-zinc-200">
                                        <flux:select wire:model.live="selectedStationId" size="sm">
                                            {{-- Add an empty first option --}}
                                            <flux:select.option value="">Choose a station...</flux:select.option>

                                            @foreach ($option->pickupStations as $station)
                                                <flux:select.option :value="$station->id">
                                                    {{ $station->name }}
                                                    {{ $station->county_id === $this->address?->county_id ? '(Nearby)' : '' }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>

                                        @if ($this->selectedStationId && $this->currentPusOption)
                                            <div class="mt-2 flex items-center justify-between text-xs">
                                                <span class="text-zinc-500">Updated cost with station surcharge</span>
                                                <span class="font-semibold">
                                                    {{ $this->currentPusOption->formattedCost() }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end mt-5">
                    <flux:button wire:click="confirm" variant="primary" class="cursor-pointer"
                        :disabled="! $this->selectedMethod">
                        Confirm Shipping Method
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:card>

    @if (app(\App\Services\Payment\PaymentService::class)->isCustom())
        <flux:card class="opacity-70 p-0 mb-4">
            <div class="px-3 py-2 flex items-center gap-1">
                <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
                <flux:heading level="3">Payment Methods</flux:heading>
            </div>
        </flux:card>
    @endif

    <flux:link :href="route('shop.index')" wire:navigate class="text-xs">
        ← Continue shopping
    </flux:link>
</div>
