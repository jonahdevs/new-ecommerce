<?php

use App\Enums\CarrierDriver;
use App\Enums\CarrierRateType;
use App\Models\CarrierRate;
use App\Models\CarrierZone;
use App\Models\DeliveryZone;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Configure Carrier — Admin')] class extends Component {
    #[Locked]
    public ShippingCarrier $shippingCarrier;

    // ==================================================
    // CARRIER DETAILS
    // ==================================================
    public string $name = '';
    public string $slug = '';
    public string $driver = '';
    public string $tracking_url_template = '';
    public int $priority = 0;
    public bool $is_active = true;
    public int $sort_order = 0;

    // ==================================================
    // CREDENTIALS (DRIVER-SPECIFIC)
    // ==================================================
    public string $cred_api_key = '';
    public string $cred_api_secret = '';
    public string $cred_pickup_lat = '';
    public string $cred_pickup_lng = '';
    public string $cred_pickup_phone = '';
    public string $cred_account_number = '';

    // ==================================================
    // ZONE COVERAGE
    // ==================================================
    public ?int $addingZoneId = null;

    // ==================================================
    // RATE EDITING
    // ==================================================
    // Keyed by "{zone_id}_{method_id}" — loaded on demand when editing a zone's rates.
    /** @var array<string, array{rate_type: string, base_rate: ?float, free_over: ?float, eta_label: string, eta_min_days: ?int, eta_max_days: ?int, is_active: bool}> */
    public array $rateForm = [];

    /** Zone whose rates are currently being edited (null = none open). */
    public ?int $editingRatesForZone = null;

    private bool $slugManuallyEdited = false;

    public function mount(ShippingCarrier $shippingCarrier): void
    {
        $this->shippingCarrier = $shippingCarrier;
        $this->name = $shippingCarrier->name;
        $this->slug = $shippingCarrier->slug;
        $this->driver = $shippingCarrier->driver->value;
        $this->tracking_url_template = (string) $shippingCarrier->tracking_url_template;
        $this->priority = (int) $shippingCarrier->priority;
        $this->is_active = (bool) $shippingCarrier->is_active;
        $this->sort_order = (int) $shippingCarrier->sort_order;
        $this->slugManuallyEdited = true;

        $creds = $shippingCarrier->credentials ?? [];
        $this->cred_api_key = $creds['api_key'] ?? '';
        $this->cred_api_secret = $creds['api_secret'] ?? '';
        $this->cred_pickup_lat = (string) ($creds['pickup_lat'] ?? '');
        $this->cred_pickup_lng = (string) ($creds['pickup_lng'] ?? '');
        $this->cred_pickup_phone = $creds['pickup_phone'] ?? '';
        $this->cred_account_number = $creds['account_number'] ?? '';
    }

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    // ==================================================
    // SAVE CARRIER DETAILS
    // ==================================================

    public function saveDetails(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('shipping_carriers', 'slug')->ignore($this->shippingCarrier->id)],
            'driver' => ['required', Rule::in(array_column(CarrierDriver::cases(), 'value'))],
            'priority' => ['integer', 'min:0'],
            'sort_order' => ['integer', 'min:0'],
            'cred_pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'cred_pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $credentials = array_filter([
            'api_key' => $this->cred_api_key ?: null,
            'api_secret' => $this->cred_api_secret ?: null,
            'pickup_lat' => $this->cred_pickup_lat ? (float) $this->cred_pickup_lat : null,
            'pickup_lng' => $this->cred_pickup_lng ? (float) $this->cred_pickup_lng : null,
            'pickup_phone' => $this->cred_pickup_phone ?: null,
            'account_number' => $this->cred_account_number ?: null,
        ]);

        $this->shippingCarrier->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'driver' => $this->driver,
            'credentials' => ! empty($credentials) ? $credentials : null,
            'tracking_url_template' => $this->tracking_url_template ?: null,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Carrier saved', text: $this->name.' has been updated.', variant: 'success');
    }

    // ==================================================
    // ZONE COVERAGE
    // ==================================================

    #[Computed]
    public function coveredZones()
    {
        return $this->shippingCarrier
            ->carrierZones()
            ->with(['deliveryZone'])
            ->get();
    }

    #[Computed]
    public function availableZones()
    {
        $coveredIds = $this->shippingCarrier->carrierZones()->pluck('delivery_zone_id')->all();

        return DeliveryZone::where('is_active', true)
            ->whereNotIn('id', $coveredIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function deliveryMethods()
    {
        return ShippingMethod::where('type', 'delivery')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);
    }

    public function addZoneCoverage(): void
    {
        if (! $this->addingZoneId) {
            return;
        }

        CarrierZone::firstOrCreate([
            'carrier_id' => $this->shippingCarrier->id,
            'delivery_zone_id' => $this->addingZoneId,
        ], ['is_active' => true]);

        // Auto-open the rate editor for the newly added zone.
        $this->openRateEditor($this->addingZoneId);

        $this->addingZoneId = null;
        unset($this->coveredZones, $this->availableZones);

        Flux::toast(heading: 'Zone added', text: 'Now set the rates for this zone.', variant: 'success');
    }

    public function removeZoneCoverage(int $zoneId): void
    {
        CarrierRate::where('carrier_id', $this->shippingCarrier->id)
            ->where('delivery_zone_id', $zoneId)
            ->delete();

        CarrierZone::where('carrier_id', $this->shippingCarrier->id)
            ->where('delivery_zone_id', $zoneId)
            ->delete();

        if ($this->editingRatesForZone === $zoneId) {
            $this->editingRatesForZone = null;
            $this->rateForm = [];
        }

        unset($this->coveredZones, $this->availableZones);
        Flux::toast(heading: 'Zone removed', text: 'Coverage and all rates for this zone have been deleted.', variant: 'success');
    }

    public function toggleZoneActive(int $zoneId): void
    {
        $cz = CarrierZone::where('carrier_id', $this->shippingCarrier->id)
            ->where('delivery_zone_id', $zoneId)
            ->firstOrFail();
        $cz->update(['is_active' => ! $cz->is_active]);
        unset($this->coveredZones);
    }

    // ==================================================
    // RATE EDITOR
    // ==================================================

    public function openRateEditor(int $zoneId): void
    {
        $this->editingRatesForZone = ($this->editingRatesForZone === $zoneId) ? null : $zoneId;

        if ($this->editingRatesForZone === null) {
            $this->rateForm = [];
            return;
        }

        // Load existing rates into the form, defaulting blanks for missing ones.
        $existing = CarrierRate::where('carrier_id', $this->shippingCarrier->id)
            ->where('delivery_zone_id', $zoneId)
            ->get()
            ->keyBy('shipping_method_id');

        $this->rateForm = [];
        foreach ($this->deliveryMethods as $method) {
            $rate = $existing[$method->id] ?? null;
            $key = "{$zoneId}_{$method->id}";
            $this->rateForm[$key] = [
                'rate_type' => $rate?->rate_type->value ?? 'fixed',
                'base_rate' => $rate ? round($rate->base_rate_cents / 100, 2) : null,
                'free_over' => $rate?->free_over_cents ? round($rate->free_over_cents / 100, 2) : null,
                'eta_label' => (string) ($rate?->eta_label ?? ''),
                'eta_min_days' => $rate?->eta_min_days,
                'eta_max_days' => $rate?->eta_max_days,
                'is_active' => $rate ? (bool) $rate->is_active : true,
            ];
        }
    }

    public function saveRates(int $zoneId): void
    {
        foreach ($this->deliveryMethods as $method) {
            $key = "{$zoneId}_{$method->id}";
            $data = $this->rateForm[$key] ?? null;
            if (! $data) {
                continue;
            }

            $this->validateOnly("rateForm.{$key}.base_rate", [
                "rateForm.{$key}.base_rate" => ['nullable', 'numeric', 'min:0'],
            ]);
            $this->validateOnly("rateForm.{$key}.free_over", [
                "rateForm.{$key}.free_over" => ['nullable', 'numeric', 'min:0'],
            ]);

            CarrierRate::updateOrCreate(
                [
                    'carrier_id' => $this->shippingCarrier->id,
                    'delivery_zone_id' => $zoneId,
                    'shipping_method_id' => $method->id,
                ],
                [
                    'rate_type' => $data['rate_type'],
                    'base_rate_cents' => $data['base_rate'] !== null ? (int) round((float) $data['base_rate'] * 100) : 0,
                    'free_over_cents' => $data['free_over'] !== null ? (int) round((float) $data['free_over'] * 100) : null,
                    'eta_label' => $data['eta_label'] ?: null,
                    'eta_min_days' => $data['eta_min_days'],
                    'eta_max_days' => $data['eta_max_days'],
                    'is_active' => (bool) $data['is_active'],
                    'sort_order' => $method->sort_order ?? 0,
                ],
            );
        }

        Flux::toast(heading: 'Rates saved', text: 'Zone rates have been updated.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.shipping.carriers.index')" wire:navigate>Carriers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">{{ $name }}</flux:heading>
        <flux:subheading>Configure zone coverage, delivery rates, and API credentials.</flux:subheading>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ================================================== --}}
        {{-- ZONE COVERAGE & RATES (MAIN COLUMN) --}}
        {{-- ================================================== --}}
        <div class="space-y-6 lg:col-span-2">

            <flux:card class="p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <div>
                        <flux:heading size="base" class="uppercase tracking-wide">Zone coverage & rates</flux:heading>
                        <flux:text size="sm" class="mt-0.5 text-zinc-500">
                            Which geographic zones this carrier serves and what it charges per method.
                        </flux:text>
                    </div>
                </div>

                {{-- Covered zones --}}
                @forelse ($this->coveredZones as $cz)
                    @php $zone = $cz->deliveryZone; @endphp
                    <div class="border-b border-zinc-200 dark:border-zinc-700">

                        {{-- Zone header --}}
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center gap-3">
                                <button wire:click="toggleZoneActive({{ $zone->id }})">
                                    <flux:badge size="sm" :color="$cz->is_active ? 'green' : 'zinc'">
                                        {{ $cz->is_active ? 'Active' : 'Paused' }}
                                    </flux:badge>
                                </button>
                                <span class="font-medium dark:text-white">{{ $zone->name }}</span>
                                <span class="text-sm text-zinc-400">{{ $zone->county }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button size="xs" variant="ghost" icon="adjustments-horizontal"
                                    wire:click="openRateEditor({{ $zone->id }})"
                                    :class="$editingRatesForZone === $zone->id ? 'text-brand-500!' : ''">
                                    Rates
                                </flux:button>
                                <flux:button size="xs" variant="ghost" icon="trash-2"
                                    wire:click="removeZoneCoverage({{ $zone->id }})"
                                    wire:confirm="Remove {{ $zone->name }} from {{ $name }}? All rates for this zone will be deleted."
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </div>

                        {{-- Rate editor (inline, toggleable) --}}
                        @if ($editingRatesForZone === $zone->id)
                            <div class="border-t border-zinc-100 bg-zinc-50 px-6 py-5 dark:border-zinc-800 dark:bg-zinc-900">
                                <flux:heading size="sm" class="mb-4">Rates in {{ $zone->name }}</flux:heading>

                                @if ($this->deliveryMethods->isEmpty())
                                    <flux:text size="sm" class="text-zinc-400">
                                        No delivery methods found. <a href="{{ route('admin.shipping.methods.create') }}" wire:navigate class="underline">Add one first.</a>
                                    </flux:text>
                                @else
                                    <div class="space-y-6">
                                        @foreach ($this->deliveryMethods as $method)
                                            @php $key = "{$zone->id}_{$method->id}"; @endphp
                                            <div class="rounded-md border border-zinc-200 dark:border-zinc-700">
                                                <div class="flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/60">
                                                    <span class="text-sm font-medium dark:text-white">{{ $method->name }}</span>
                                                    <flux:switch
                                                        wire:model="rateForm.{{ $key }}.is_active"
                                                        label="Active" />
                                                </div>
                                                <div class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-3">
                                                    <flux:select wire:model.live="rateForm.{{ $key }}.rate_type" label="Rate type">
                                                        @foreach (CarrierRateType::cases() as $rt)
                                                            <flux:select.option :value="$rt->value">{{ $rt->label() }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>

                                                    @if (($rateForm[$key]['rate_type'] ?? 'fixed') === 'fixed')
                                                        <flux:input wire:model="rateForm.{{ $key }}.base_rate"
                                                            label="Rate (KES)" type="number" min="0" step="1" placeholder="0" />
                                                        <flux:input wire:model="rateForm.{{ $key }}.free_over"
                                                            label="Free over (KES)" type="number" min="0" step="1"
                                                            placeholder="Optional" />
                                                    @endif

                                                    <flux:input wire:model="rateForm.{{ $key }}.eta_label"
                                                        label="ETA label" placeholder="e.g. Same day" />
                                                    <flux:input wire:model="rateForm.{{ $key }}.eta_min_days"
                                                        label="Min days" type="number" min="0" placeholder="0" />
                                                    <flux:input wire:model="rateForm.{{ $key }}.eta_max_days"
                                                        label="Max days" type="number" min="0" placeholder="0" />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button variant="primary" wire:click="saveRates({{ $zone->id }})">
                                            Save rates
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <flux:icon.map-pin class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="mt-3 font-medium">No zones covered yet</flux:text>
                        <flux:text size="sm" class="mt-1 text-zinc-400">
                            Add a zone below to start delivering with this carrier.
                        </flux:text>
                    </div>
                @endforelse

                {{-- Add zone --}}
                @if ($this->availableZones->isNotEmpty())
                    <div class="flex items-center gap-3 px-6 py-4">
                        <flux:select wire:model="addingZoneId" class="flex-1">
                            <flux:select.option value="">Select a zone to cover…</flux:select.option>
                            @foreach ($this->availableZones as $zone)
                                <flux:select.option :value="$zone->id">{{ $zone->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button icon="plus" wire:click="addZoneCoverage" :disabled="!$addingZoneId">
                            Add
                        </flux:button>
                    </div>
                @elseif ($this->coveredZones->isNotEmpty())
                    <div class="px-6 py-4 text-sm text-zinc-400">
                        All active delivery zones are covered by this carrier.
                    </div>
                @endif
            </flux:card>

        </div>

        {{-- ================================================== --}}
        {{-- CARRIER DETAILS (SIDEBAR) --}}
        {{-- ================================================== --}}
        <div class="space-y-6">

            {{-- Details form --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="base" class="uppercase tracking-wide">Carrier details</flux:heading>
                </div>

                <form wire:submit="saveDetails" class="space-y-4 p-6">
                    <flux:input wire:model.live.debounce.400ms="name" label="Name" required />
                    <flux:input wire:model.blur="slug" label="Slug" />

                    <flux:select wire:model.live="driver" label="Driver"
                        description="Determines how orders are dispatched.">
                        @foreach (CarrierDriver::cases() as $d)
                            <flux:select.option :value="$d->value">{{ $d->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="priority" label="Priority" type="number" min="0"
                        description="Higher priority wins when multiple carriers cover the same zone." />

                    <flux:input wire:model="tracking_url_template" label="Tracking URL"
                        placeholder="https://track.carrier.co.ke/{number}"
                        description="Use {number} as the tracking number placeholder." />

                    <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />

                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <flux:label>Active</flux:label>
                        <flux:switch wire:model="is_active" />
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full">Save details</flux:button>
                </form>
            </flux:card>

            {{-- Credentials (shown for non-self-managed drivers) --}}
            @if ($driver !== 'self_managed')
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="base" class="uppercase tracking-wide">API credentials</flux:heading>
                        <flux:text size="sm" class="mt-0.5 text-zinc-500">Stored encrypted. Never exposed in responses.</flux:text>
                    </div>
                    <div class="space-y-4 p-6">
                        @if ($driver === 'fargo')
                            <flux:text size="sm" class="rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-700 dark:border-amber-700/40 dark:bg-amber-900/20 dark:text-amber-300">
                                Fargo Courier uses a manual waybill workflow — no API credentials needed. Create waybills on the Fargo portal and enter the number on each shipment.
                            </flux:text>
                        @else
                            <div class="grid grid-cols-1 gap-4">
                                <flux:input wire:model="cred_api_key" label="API key"
                                    type="password" autocomplete="new-password" placeholder="API key" />
                                <flux:input wire:model="cred_api_secret" label="API secret"
                                    type="password" autocomplete="new-password" placeholder="Secret / token" />
                            </div>

                            @if (in_array($driver, ['dhl', 'aramex']))
                                <flux:input wire:model="cred_account_number" label="Account number" />
                            @endif

                            <flux:button wire:click="saveDetails" variant="primary" class="w-full">Save credentials</flux:button>
                        @endif
                    </div>
                </flux:card>
            @endif

        </div>

    </div>
</div>
