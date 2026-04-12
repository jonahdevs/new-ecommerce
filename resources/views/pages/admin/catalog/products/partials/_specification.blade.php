<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Technical Specification</flux:heading>

        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5">
        <x-rich-editor
            model="form.technical_specification"
            :value="$this->form->technical_specification ?? ''"
            placeholder="Enter technical specifications, dimensions, materials, certifications..." />
        <flux:error name="form.technical_specification" />
    </div>
</flux:card>
