{{-- Step 1 body — pin the delivery location. Requires the addressMap() Alpine scope. --}}
<div class="flex items-center justify-between">
    <flux:label>Pin location</flux:label>
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
     class="h-72 w-full overflow-hidden rounded-md border border-zinc-200 bg-surface-sunken">
</div>

<flux:text size="sm" class="text-ink-4">
    Click the map or use "Use my location" to drop a pin. Drag it to fine-tune.
</flux:text>

{{-- Live serviceability feedback for the dropped pin. --}}
@if ($this->latitude !== null && $this->longitude !== null)
    @if ($this->pinnedZone)
        <div class="flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[12.5px] font-medium text-emerald-700">
            <flux:icon.check-circle variant="micro" class="size-4" />
            We deliver to {{ $this->pinnedZone->name }}{{ $this->pinnedZone->eta_label ? ' · '.$this->pinnedZone->eta_label : '' }}.
        </div>
    @else
        <div class="flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[12.5px] font-medium text-amber-700">
            <flux:icon.exclamation-triangle variant="micro" class="size-4" />
            This spot is outside our delivery areas. You can still save it, but delivery may be unavailable at checkout.
        </div>
    @endif
@endif

@error('latitude') <flux:error>{{ $message }}</flux:error> @enderror
