<?php

use App\Models\Address;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Addresses — Sheffield')] class extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

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

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->orderBy('created_at')->get();
    }

    public function rules(): array
    {
        return [
            'label'       => ['required', 'string', 'max:50'],
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'line1'       => ['required', 'string', 'max:255'],
            'line2'       => ['nullable', 'string', 'max:255'],
            'city'        => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country'     => ['required', 'string', 'size:2'],
            'is_default'  => ['boolean'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $this->editingId    = $id;
        $this->label        = $address->label;
        $this->first_name   = $address->first_name;
        $this->last_name    = $address->last_name;
        $this->phone        = $address->phone ?? '';
        $this->line1        = $address->line1;
        $this->line2        = $address->line2 ?? '';
        $this->city         = $address->city;
        $this->postal_code  = $address->postal_code ?? '';
        $this->country      = $address->country;
        $this->is_default   = $address->is_default;
        $this->latitude     = $address->latitude;
        $this->longitude    = $address->longitude;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($data['is_default']) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if ($this->editingId) {
            auth()->user()->addresses()->findOrFail($this->editingId)->update($data);
            Flux::toast(heading: 'Address updated', text: 'Your address has been saved.');
        } else {
            if (auth()->user()->addresses()->count() === 0) {
                $data['is_default'] = true;
            }
            auth()->user()->addresses()->create($data);
            Flux::toast(heading: 'Address added', text: 'Your new address has been saved.');
        }

        $this->showModal = false;
        unset($this->addresses);
    }

    public function setDefault(int $id): void
    {
        auth()->user()->addresses()->update(['is_default' => false]);
        auth()->user()->addresses()->findOrFail($id)->update(['is_default' => true]);
        unset($this->addresses);
    }

    public function delete(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id)->delete();
        unset($this->addresses);
        Flux::toast(heading: 'Address removed', text: 'The address has been deleted.', variant: 'warning');
    }

    private function resetForm(): void
    {
        $this->label       = 'Home';
        $this->first_name  = '';
        $this->last_name   = '';
        $this->phone       = '';
        $this->line1       = '';
        $this->line2       = '';
        $this->city        = 'Nairobi';
        $this->postal_code = '';
        $this->country     = 'KE';
        $this->is_default  = false;
        $this->latitude    = null;
        $this->longitude   = null;
        $this->resetValidation();
    }
}; ?>

@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLs=" crossorigin=""></script>
@endassets

@script
<script>
Alpine.data('addressMap', () => ({
    map: null,
    marker: null,
    locating: false,

    init() {
        this.$watch('$wire.showModal', (open) => {
            if (open) {
                this.$nextTick(() => this.initMap());
            } else {
                this.destroyMap();
            }
        });
    },

    initMap() {
        if (this.map) this.destroyMap();

        const lat = this.$wire.latitude ?? -1.2921;
        const lng = this.$wire.longitude ?? 36.8219;
        const hasPin = this.$wire.latitude !== null;

        this.map = L.map(this.$refs.mapContainer, { zoomControl: true }).setView([lat, lng], hasPin ? 15 : 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);

        if (hasPin) {
            this.placeMarker(lat, lng);
        }

        this.map.on('click', (e) => {
            this.placeMarker(e.latlng.lat, e.latlng.lng);
        });
    },

    placeMarker(lat, lng) {
        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        } else {
            this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
            this.marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.$wire.latitude  = parseFloat(pos.lat.toFixed(7));
                this.$wire.longitude = parseFloat(pos.lng.toFixed(7));
            });
        }
        this.$wire.latitude  = parseFloat(lat.toFixed(7));
        this.$wire.longitude = parseFloat(lng.toFixed(7));
        this.map.panTo([lat, lng]);
    },

    clearPin() {
        if (this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
        }
        this.$wire.latitude  = null;
        this.$wire.longitude = null;
    },

    locateMe() {
        if (!navigator.geolocation) return;
        this.locating = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.locating = false;
                this.placeMarker(pos.coords.latitude, pos.coords.longitude);
                this.map.setView([pos.coords.latitude, pos.coords.longitude], 16);
            },
            () => { this.locating = false; },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    },

    destroyMap() {
        if (this.map) { this.map.remove(); this.map = null; this.marker = null; }
    },
}));
</script>
@endscript

<div class="page-fade" x-data="addressMap()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Addresses</flux:heading>
            <flux:text class="mt-1">Manage your saved delivery addresses.</flux:text>
        </div>
        <flux:button variant="customer-primary" size="customer" wire:click="openCreate" icon="plus">
            Add address
        </flux:button>
    </div>

    {{-- Address cards --}}
    <div class="mt-6">
        @if ($this->addresses->isEmpty())
            <flux:card class="py-14 text-center">
                <flux:icon.map-pin variant="outline" class="mx-auto size-9 text-ink-4" />
                <flux:heading size="sm" class="mt-4">No addresses saved</flux:heading>
                <flux:text class="mt-1">Add a delivery address to speed up checkout.</flux:text>
                <flux:button variant="customer-primary" size="customer" wire:click="openCreate" class="mt-5">
                    Add address
                </flux:button>
            </flux:card>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($this->addresses as $address)
                    <div wire:key="addr-{{ $address->id }}"
                         class="relative rounded-md border bg-white p-5 {{ $address->is_default ? 'border-brand-500' : 'border-zinc-200' }}">

                        @if ($address->is_default)
                            <div class="absolute right-4 top-4">
                                <span class="rounded-full bg-brand-500/10 px-2.5 py-0.5 text-[10.5px] font-bold tracking-wide text-brand-500 uppercase">
                                    Default
                                </span>
                            </div>
                        @endif

                        <div class="text-[10.5px] font-bold tracking-[0.1em] text-ink-3 uppercase">{{ $address->label }}</div>
                        <div class="mt-1 font-semibold text-ink">{{ $address->fullName() }}</div>

                        <div class="mt-2 space-y-0.5 text-[13px] leading-relaxed text-ink-2">
                            <div>{{ $address->line1 }}</div>
                            @if ($address->line2)
                                <div>{{ $address->line2 }}</div>
                            @endif
                            <div>{{ $address->city }}{{ $address->postal_code ? ', ' . $address->postal_code : '' }}</div>
                            @if ($address->phone)
                                <flux:text size="sm" class="mt-1 text-ink-3">{{ $address->phone }}</flux:text>
                            @endif
                        </div>

                        {{-- Pin indicator --}}
                        @if ($address->hasCoordinates())
                            <a href="https://www.google.com/maps?q={{ $address->latitude }},{{ $address->longitude }}"
                               target="_blank"
                               class="mt-3 inline-flex items-center gap-1.5 text-[12px] font-semibold text-brand-500 hover:text-brand-600">
                                <flux:icon.map-pin variant="micro" class="size-3.5" />
                                View on map
                            </a>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <flux:button variant="customer-outline" size="customer" wire:click="openEdit({{ $address->id }})">
                                Edit
                            </flux:button>
                            @if (!$address->is_default)
                                <flux:button variant="ghost" size="xs" wire:click="setDefault({{ $address->id }})">
                                    Set as default
                                </flux:button>
                                <flux:button variant="ghost" size="xs"
                                             wire:click="delete({{ $address->id }})"
                                             wire:confirm="Delete this address?"
                                             class="text-red-500! hover:text-red-600!">
                                    Delete
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Create / Edit modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[560px]" :dismissible="false">
        <flux:heading>{{ $editingId ? 'Edit address' : 'New address' }}</flux:heading>
        <flux:subheading>{{ $editingId ? 'Update your delivery address details.' : 'Add a new delivery address to your account.' }}</flux:subheading>

        <form wire:submit="save" class="mt-6 space-y-4">

            {{-- Label --}}
            <flux:field>
                <flux:label>Label</flux:label>
                <flux:select wire:model="label">
                    @foreach (['Home', 'Office', 'Warehouse', 'Site', 'Other'] as $opt)
                        <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="label" />
            </flux:field>

            {{-- Name --}}
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>First name</flux:label>
                    <flux:input wire:model="first_name" placeholder="Anita" />
                    <flux:error name="first_name" />
                </flux:field>
                <flux:field>
                    <flux:label>Last name</flux:label>
                    <flux:input wire:model="last_name" placeholder="Wanjiru" />
                    <flux:error name="last_name" />
                </flux:field>
            </div>

            {{-- Phone --}}
            <flux:field>
                <flux:label badge="Optional">Phone</flux:label>
                <flux:input wire:model="phone" type="tel" placeholder="+254 712 345 678" />
                <flux:error name="phone" />
            </flux:field>

            {{-- Address lines --}}
            <flux:field>
                <flux:label>Address line 1</flux:label>
                <flux:input wire:model="line1" placeholder="Street, building, floor" />
                <flux:error name="line1" />
            </flux:field>
            <flux:field>
                <flux:label badge="Optional">Address line 2</flux:label>
                <flux:input wire:model="line2" placeholder="Suite / Unit" />
                <flux:error name="line2" />
            </flux:field>

            {{-- City / postal / country --}}
            <div class="grid grid-cols-3 gap-4">
                <flux:field class="col-span-2">
                    <flux:label>City</flux:label>
                    <flux:input wire:model="city" placeholder="Nairobi" />
                    <flux:error name="city" />
                </flux:field>
                <flux:field>
                    <flux:label>Postal code</flux:label>
                    <flux:input wire:model="postal_code" placeholder="00100" />
                    <flux:error name="postal_code" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Country</flux:label>
                <flux:select wire:model="country">
                    <flux:select.option value="KE">Kenya</flux:select.option>
                    <flux:select.option value="UG">Uganda</flux:select.option>
                    <flux:select.option value="TZ">Tanzania</flux:select.option>
                    <flux:select.option value="RW">Rwanda</flux:select.option>
                </flux:select>
                <flux:error name="country" />
            </flux:field>

            {{-- Map pin --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <flux:label>Pin location <flux:badge size="sm" variant="pill">Optional</flux:badge></flux:label>
                    <div class="flex items-center gap-2">
                        <flux:button type="button" size="xs" variant="ghost" icon="map-pin"
                                     x-on:click="locateMe()" x-bind:disabled="locating">
                            <span x-text="locating ? 'Locating…' : 'Use my location'"></span>
                        </flux:button>
                        <template x-if="$wire.latitude !== null">
                            <flux:button type="button" size="xs" variant="ghost"
                                         x-on:click="clearPin()"
                                         class="text-red-500! hover:text-red-600!">
                                Clear pin
                            </flux:button>
                        </template>
                    </div>
                </div>

                <div x-ref="mapContainer"
                     class="h-56 w-full overflow-hidden rounded-md border border-zinc-200 bg-surface-sunken">
                </div>

                <flux:text size="sm" class="text-ink-4">
                    Click the map or use "Use my location" to drop a pin. Drag it to fine-tune.
                </flux:text>

                @error('latitude') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <flux:checkbox wire:model="is_default" label="Set as default address" />

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$flux.close()">Cancel</flux:button>
                <flux:button type="submit" variant="customer-primary" size="customer">
                    {{ $editingId ? 'Save changes' : 'Add address' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

</div>
