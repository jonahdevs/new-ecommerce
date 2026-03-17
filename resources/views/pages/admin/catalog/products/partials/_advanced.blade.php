{{-- Advanced --}}
<div wire:cloak wire:show="activeTab == 'advanced'" class="space-y-8 p-5">
    {{-- ================================================ --}}
    {{-- PURCHASE NOTE                                    --}}
    {{-- ================================================ --}}
    <flux:textarea wire:model="form.purchase_note" label="Purchase Note" rows="3"
        placeholder="e.g. Our installation team will contact you within 24 hours..." />

    <flux:separator />

    {{-- ================================================ --}}
    {{-- QUOTATION                                        --}}
    {{-- ================================================ --}}
    <div class="space-y-5">
        {{-- Enable Reviews --}}
        <flux:checkbox label="" wire:model="form.requires_quotation"
            label="This product requires a quotation before purchase" />


        <div wire:show="form.requires_quotation" wire:cloak class="space-y-5">

            {{-- Min Order Quantity --}}
            <flux:input type="number" min="1" step="0.01" wire:model="form.min_order_quantity"
                label="Minimum Order Quantity" placeholder="e.g. 10" />

            {{-- Quotation Notes --}}
            <flux:textarea wire:model="form.quotation_notes" label="Quotation Notes"
                placeholder="e.g. Bulk discounts available for orders over 100 units. Lead time is 2–3 weeks."
                rows="3" />
        </div>
    </div>


    <flux:separator />

    <div class="space-y-5">
        {{-- Sort Order --}}
        <flux:input type="number" min="0" wire:model="form.sort_order" label="Sort Order" placeholder="0" />


        {{-- Enable Reviews --}}
        <flux:checkbox label="" wire:model="form.reviews_enabled" label="Enable Reviews" />
    </div>

</div>
