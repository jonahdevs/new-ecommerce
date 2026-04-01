<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    {{-- Left Column --}}
    <div class="md:col-span-2 space-y-5">
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">General Information</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Brand Name" wire:model="form.name" placeholder="e.g. Nike" />
                    <flux:input label="Slug" wire:model="form.slug" placeholder="auto-generated" />
                </div>

                <flux:textarea label="Description" wire:model="form.description" rows="4"
                    placeholder="Brief description of the brand..." />

                <flux:input label="Website URL" wire:model="form.website_url" placeholder="https://example.com"
                    type="url" />

                <flux:input label="Sort Order" wire:model="form.sort_order" type="number" min="0"
                    placeholder="0" />
            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">SEO Metadata</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                <flux:input label="Meta Title" wire:model="form.meta_title" placeholder="Best products from..." />
                <flux:textarea label="Meta Description" wire:model="form.meta_description" rows="3" />
                <flux:input label="Meta Keywords (Comma separated)" wire:model="form.meta_keywords"
                    placeholder="brand, products, quality" />
            </div>
        </flux:card>
    </div>

    {{-- Right Column --}}
    <div class="space-y-6">
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">Status</flux:heading>
            </div>
            <div class="p-5">
                <flux:switch label="Active Status" wire:model="form.is_active" description="Show on storefront" />
            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="px-3 py-2 border-b  dark:border-b-zinc-600">
                <flux:heading size="lg">Brand Logo</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                @if ($form->logo_path)
                    <div class="relative">
                        <img src="{{ $form->logo_path->temporaryUrl() }}"
                            class="w-full max-w-xs rounded border object-contain bg-white p-4">
                        <button type="button" wire:click="$set('form.logo_path', null)"
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1">
                            <flux:icon name="x-mark" variant="micro" />
                        </button>
                    </div>
                @elseif(isset($brand) && $brand->logo_path)
                    <img src="{{ asset('storage/' . $brand->logo_path) }}"
                        class="w-full max-w-xs rounded border object-contain bg-white p-4">
                @endif
                <flux:input type="file" wire:model="form.logo_path" accept="image/*" />
                <p class="text-xs text-zinc-500">Recommended: Square logo, transparent background</p>
            </div>
        </flux:card>
    </div>
</div>
