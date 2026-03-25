{{-- Basic Information Section --}}
<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="px-3 py-2 flex items-center justify-between dark:border-zinc-600" :class="{ 'border-b ': expanded }">
        <flux:heading>Basic Information</flux:heading>

        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5 space-y-5">
        {{-- Product Name --}}
        <flux:input :label="__('Product Name')" wire:model="form.name" />

        <div class="grid grid-cols-2 gap-5">
            {{-- Model Number --}}
            <flux:input :label="__('Model Number')" wire:model="form.model_number" />

            {{-- Slug --}}
            <flux:input :label="__('Slug')" wire:model="form.slug" />
        </div>

        <flux:field>
            <flux:label>{{ __('Short Description') }}</flux:label>
            <x-my-markdown wire:model="form.short_description" />
            <flux:error name="form.short_description" />


        </flux:field>
    </div>
</flux:card>
