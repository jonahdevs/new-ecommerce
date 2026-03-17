{{-- Shipping --}}
<div wire:cloak wire:show="activeTab == 'shipping'" class="space-y-5 p-5">
    <flux:input type="number" wire:model="form.weight" label=" Weight (kg)" placeholder="0.00" step="0.01"
        min="0" />

    <flux:field>
        <flux:label>Dimensions</flux:label>

        <flux:input.group>
            <flux:input type="number" wire:model="form.length" placeholder="Length (0.00)" step="0.01"
                min="0" />

            <flux:input type="number" wire:model="form.width" placeholder="Width (0.00)" step="0.01"
                min="0" />

            <flux:input type="number" wire:model="form.height" placeholder="Height (0.00)" step="0.01"
                min="0" />
        </flux:input.group>

        <flux:error name="form.length" />
        <flux:error name="form.width" />
        <flux:error name="form.height" />
    </flux:field>
</div>
