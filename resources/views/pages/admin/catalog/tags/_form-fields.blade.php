<flux:card class="space-y-5">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Name --}}
        <flux:input label="Name" wire:model.live="form.name" placeholder="e.g., New Arrival, Featured, Sale"
            description:trailing="Slug will be auto-generated." />

        {{-- Type --}}
        <flux:field>
            <flux:label>Type (optional)
            </flux:label>
            <flux:input wire:model="form.type" placeholder="e.g., color, size, category"
                description:trailing="Group tags by type for better organization and filtering." />
            <flux:error name="form.type" />
        </flux:field>
    </div>

    {{-- Order --}}
    <flux:input label="Order" wire:model="form.order_column" type="number" min="0"
        description:trailing="Lower numbers appear first." />
</flux:card>
