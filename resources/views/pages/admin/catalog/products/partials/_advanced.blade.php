{{-- Advanced --}}
<div wire:cloak wire:show="activeTab == 'advanced'" class="space-y-5">

    {{-- Purchase Note --}}
    <flux:textarea wire:model="form.purchase_note" label="Purchase Note" rows="3"
        placeholder="e.g. Our installation team will contact you within 24 hours..." />


    <flux:separator />

    {{-- Menu Order --}}
    <flux:input type="number" min="0" wire:model="form.sort_order" label="Menu Order" placeholder="0" />

    <flux:separator />

    {{-- Enable Reviews --}}
    <flux:checkbox label="" wire:model="form.reviews_enabled" label="Enable Reviews" />

</div>
