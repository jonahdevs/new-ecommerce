<flux:card class="space-y-5">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Name --}}
        <flux:input label="Name" wire:model="form.name" placeholder="e.g., New Arrival, Featured, Sale"
            description:trailing="Slug will be auto-generated." />

        {{-- Type --}}
        <flux:field>
            <flux:label>Type (optional)</flux:label>
            <flux:input wire:model="form.type" placeholder="e.g., color, size, category"
                description:trailing="Group tags by type for better organization and filtering." />
            <flux:error name="form.type" />
        </flux:field>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Color --}}
        <flux:field>
            <flux:label>Badge Color</flux:label>
            <div class="flex items-center gap-3">
                <input type="color" wire:model.live="form.color"
                    class="h-9 w-14 rounded-md border border-zinc-300 dark:border-zinc-600 cursor-pointer p-0.5 bg-white dark:bg-zinc-800" />
                <flux:input wire:model.live="form.color" placeholder="#6b7280" class="font-mono" />
            </div>
            <flux:error name="form.color" />
            <p class="text-xs text-zinc-400 mt-1">Hex color shown on the product card badge.</p>
        </flux:field>

        {{-- Order --}}
        <flux:field>
            <flux:label>Order</flux:label>
            <flux:input wire:model="form.order_column" type="number" min="0" />
            <p class="text-xs text-zinc-400 mt-1">Lower numbers appear first. Determines badge priority when a product
                has multiple tags.</p>
            <flux:error name="form.order_column" />
        </flux:field>
    </div>

    {{-- Preview --}}
    <div>
        <flux:label class="mb-2">Badge Preview</flux:label>
        <span class="rounded-e-full px-2.5 py-1 text-xs font-medium text-white tracking-wide shadow-sm"
            style="background-color: {{ $form->color ?: '#6b7280' }}">
            {{ $form->name ?: 'Tag Name' }}
        </span>
    </div>

</flux:card>
