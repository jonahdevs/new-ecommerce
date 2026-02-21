<flux:card class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Name --}}
        <flux:input label="Name" wire:model.live="form.name" placeholder="e.g., New arrival, Featured, Sale" />

        {{-- Slug --}}
        <flux:input label="Slug" wire:model.live="form.slug" placeholder="e.g., new-arrival"
            description:trailing="URL-friendly version of the name. Leave blank to auto-generate." />
    </div>

    {{-- Description --}}
    <flux:textarea label="Description" wire:model="form.description" rows="3"
        placeholder="Optional description for this tag..." />

    {{-- Color --}}
    <flux:field>
        <flux:label>Color *</flux:label>
        <div class="flex items-center gap-3">
            <input type="color" wire:model.live="form.color"
                class="h-10 w-20 rounded border border-zinc-300 cursor-pointer" />
            <flux:input wire:model.live="form.color" placeholder="#6366F1" class="flex-1" />
        </div>
        <flux:error name="form.color" />
        <flux:description>Choose a color to represent this tag.</flux:description>
    </flux:field>

    {{-- Sort Order --}}
    <flux:input label="Sort Order" wire:model="form.sort_order" type="number" min="0"
        description:trailing="Lower numbers appear first. Leave blank for default ordering." />

    {{-- Active Status --}}
    <flux:field>
        <div class="flex items-center gap-3">
            <flux:switch wire:model="form.is_active" />
            <flux:label>Active</flux:label>
        </div>
        <flux:description>Inactive tags won't be shown to customers.</flux:description>
    </flux:field>
</flux:card>
