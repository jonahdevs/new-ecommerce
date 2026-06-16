{{-- Shared showroom form fields (two-column layout). --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Main column --}}
    <div class="space-y-6 lg:col-span-2">

        {{-- Details --}}
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="base" class="uppercase tracking-wide">Showroom details</flux:heading>
            </div>
            <div class="space-y-4 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>City</flux:label>
                        <flux:input wire:model="city" placeholder="Nairobi" autofocus />
                        <flux:error name="city" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Country</flux:label>
                        <flux:input wire:model="country" placeholder="Kenya" />
                        <flux:error name="country" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Address</flux:label>
                    <flux:input wire:model="address" placeholder="Off Old Mombasa Road…" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field>
                    <flux:label>P.O. Box</flux:label>
                    <flux:input wire:model="pobox" placeholder="Optional" />
                    <flux:error name="pobox" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Map coordinates --}}
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="base" class="uppercase tracking-wide">Map coordinates</flux:heading>
            </div>
            <div
                x-data="{
                    locating: false,
                    geoError: '',
                    locate() {
                        this.geoError = '';
                        if (! navigator.geolocation) {
                            this.geoError = 'Your browser cannot share a location. Enter the coordinates manually.';
                            return;
                        }
                        this.locating = true;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.locating = false;
                                $wire.set('latitude', pos.coords.latitude.toFixed(6));
                                $wire.set('longitude', pos.coords.longitude.toFixed(6));
                            },
                            () => {
                                this.locating = false;
                                this.geoError = 'Could not get your location. Allow location access or enter the coordinates manually.';
                            },
                            { enableHighAccuracy: true, timeout: 10000 },
                        );
                    },
                }"
                class="space-y-3 p-6"
            >
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Latitude</flux:label>
                        <flux:input wire:model="latitude" placeholder="-1.2921" inputmode="decimal" />
                        <flux:error name="latitude" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Longitude</flux:label>
                        <flux:input wire:model="longitude" placeholder="36.8219" inputmode="decimal" />
                        <flux:error name="longitude" />
                    </flux:field>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="button" size="sm" variant="filled" icon="map-pin"
                        x-on:click="locate()" x-bind:disabled="locating">
                        <span x-show="! locating">Use my current location</span>
                        <span x-show="locating" x-cloak>Locating…</span>
                    </flux:button>
                    <flux:text size="sm" class="text-zinc-400">Optional — used to pin the branch on the map.</flux:text>
                </div>

                <flux:text size="sm" x-show="geoError" x-cloak x-text="geoError" class="text-red-500!" />
            </div>
        </flux:card>

        {{-- Contact --}}
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="base" class="uppercase tracking-wide">Contact</flux:heading>
            </div>
            <div class="space-y-4 p-6">
                <flux:field>
                    <flux:label>Phone numbers</flux:label>
                    <flux:input wire:model="phonesInput" placeholder="+254 713 777 111, +254 713 444 000" />
                    <flux:description>Separate multiple numbers with commas.</flux:description>
                    <flux:error name="phonesInput" />
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="branch@store.com" />
                    <flux:error name="email" />
                </flux:field>
            </div>
        </flux:card>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Settings</flux:heading>
            </div>
            <div class="space-y-4 p-6">
                <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <flux:label>Headquarters</flux:label>
                    <flux:switch wire:model="is_hq" />
                </div>
                <flux:field>
                    <flux:label>Sort order</flux:label>
                    <flux:input type="number" wire:model="sort_order" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>
            </div>
        </flux:card>
    </div>
</div>
