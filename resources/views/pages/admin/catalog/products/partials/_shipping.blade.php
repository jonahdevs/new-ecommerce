{{-- Shipping --}}
@php
    $regionalSettings = app(\App\Settings\RegionalSettings::class);
    $weightUnit = $regionalSettings->weight_unit;
    $dimensionUnit = $regionalSettings->dimension_unit;
@endphp
<div wire:cloak wire:show="activeTab == 'shipping'" class="space-y-5 p-5">
    <flux:input type="number" wire:model="form.weight" label="Weight ({{ $weightUnit }})" placeholder="0.00" step="0.01"
        min="0" />

    <flux:field>
        <flux:label>Dimensions ({{ $dimensionUnit }})</flux:label>

        <flux:input.group>
            <flux:input type="number" wire:model="form.length" placeholder="Length" step="0.01"
                min="0" />

            <flux:input type="number" wire:model="form.width" placeholder="Width" step="0.01"
                min="0" />

            <flux:input type="number" wire:model="form.height" placeholder="Height" step="0.01"
                min="0" />
        </flux:input.group>

        <flux:error name="form.length" />
        <flux:error name="form.width" />
        <flux:error name="form.height" />
    </flux:field>


    <flux:separator />

    {{-- ── Delivery & Policy Information ── --}}
    <div class="space-y-5">

        {{-- Shipping Information --}}
        <flux:textarea wire:model="form.shipping_information" label="Shipping Information"
            placeholder="e.g. This item requires a delivery vehicle with a tail lift. Our installation team will contact you within 48 hours to schedule delivery."
            rows="3" />

        {{-- Warranty Information --}}
        <flux:textarea wire:model="form.warranty_information" label="Warranty Information"
            placeholder="e.g. This product comes with a 12-month manufacturer warranty covering defects in materials and workmanship. Does not cover normal wear and tear or damage caused by misuse."
            rows="3" />

        {{-- Return Policy --}}
        <flux:textarea wire:model="form.return_policy" label="Return Policy"
            placeholder="e.g. Non-returnable — this item is made to order. For standard stock items, returns are accepted within 30 days in original packaging."
            rows="3" />
    </div>

</div>
