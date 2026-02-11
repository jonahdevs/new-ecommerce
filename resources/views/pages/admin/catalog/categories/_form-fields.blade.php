<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Left Column --}}
    <div class="md:col-span-2 space-y-6">
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b">
                <flux:heading size="lg">General Information</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Category Name" wire:model="form.name" />
                    <flux:input label="Slug" wire:model="form.slug" placeholder="auto-generated" />
                </div>

                <flux:textarea label="Description" wire:model="form.description" rows="4" />

                <flux:select label="Parent Category" wire:model="form.parent_id">
                    <option value="">Root (No Parent)</option>
                    @foreach ($this->parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="px-3 py-2 border-b">
                <flux:heading size="lg">SEO Metadata</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                <flux:input label="Meta Title" wire:model="form.meta_title" />
                <flux:textarea label="Meta Description" wire:model="form.meta_description" />
            </div>
        </flux:card>
    </div>

    {{-- Right Column --}}
    <div class="space-y-6">
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b">
                <flux:heading size="lg">Visibility</flux:heading>
            </div>
            <div class="p-5 space-y-5">
                <flux:switch label="Active Status" wire:model="form.is_active" />
                <flux:switch label="Featured Category" wire:model="form.is_featured" />
                <flux:switch label="Show in Navbar" wire:model="form.show_in_navbar" />
            </div>
        </flux:card>

        <flux:card class="p-0">
            <div class="px-3 py-2 border-b">
                <flux:heading size="lg">Icons & Media</flux:heading>
            </div>
            <div class="p-5 space-y-5">
                <div class="space-y-2">
                    <flux:label>Category Icon (Image)</flux:label>
                    @if ($form->image_icon)
                        <img src="{{ $form->image_icon->temporaryUrl() }}" class="w-16 h-16 rounded border">
                    @elseif(isset($category) && $category->image_icon)
                        <img src="{{ asset('storage/' . $category->image_icon) }}" class="w-16 h-16 rounded border">
                    @endif
                    <flux:input type="file" wire:model="form.image_icon" size="sm" />
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-2">
                    <flux:textarea label="Icon SVG Code" wire:model.live="form.icon_svg" placeholder="<svg>...</svg>"
                        rows="3" />
                    @if ($form->icon_svg)
                        <div class="p-2 border rounded bg-zinc-50 w-12 h-12 flex items-center justify-center">
                            {!! $form->icon_svg !!}
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
        <flux:card class="p-0">
            <div class="border-b px-3 py-2">
                <flux:heading size="lg">Category Banner</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                @if ($form->image_path)
                    <img src="{{ $form->image_path->temporaryUrl() }}"
                        class="w-full aspect-video rounded border object-cover">
                @elseif(isset($category) && $category->image_path)
                    <img src="{{ asset('storage/' . $category->image_path) }}"
                        class="w-full aspect-video rounded border object-cover">
                @endif
                <flux:input type="file" wire:model="form.image_path" />
            </div>
        </flux:card>
    </div>
</div>
