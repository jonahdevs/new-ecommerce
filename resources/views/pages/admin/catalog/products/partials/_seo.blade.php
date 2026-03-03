<flux:card x-data="{ expanded: true }" class="p-0">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b': expanded }">
        <flux:heading>SEO & Meta Information</flux:heading>

        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5 space-y-5">
        <!-- Meta title -->
        <flux:input wire:model="form.meta_title" :label="__('Meta Title')" wire:model="form.meta_title"
            placeholder="SEO title for this product" />

        <!-- Meta description -->
        <flux:textarea wire:model="form.meta_description" :label="__('Meta Description')"
            wire:model="form.meta_description" rows="3" placeholder="SEO description for this product" />

        <!-- Meta keywords -->
        <flux:input wire:model="form.meta_keywords" :label="__('Meta Keywords')"
            placeholder="keyword1, keyword2, keyword3" description:trailing="Separate keywords with commas" />

        <flux:field>
            <flux:label>{{ __('Canonical URL') }}</flux:label>
            <flux:input.group>
                <flux:input.group.prefix>{{ config('app.url') }}</flux:input.group.prefix>
                <flux:input wire:model="form.canonical_url" placeholder="products" />
            </flux:input.group>

            <flux:error name="form.canonical_url" />
        </flux:field>
    </div>

</flux:card>
