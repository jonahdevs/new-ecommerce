<div class="grid grid-cols-1 md:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="md:col-span-2 space-y-5">

        {{-- General Information --}}
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">General Information</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Category Name" wire:model.live.debounce.300ms="form.name" placeholder="e.g. Refrigeration" />
                    <flux:input label="Slug" wire:model="form.slug" placeholder="auto-generated from name" />
                </div>

                <flux:textarea label="Description" wire:model="form.description" rows="4"
                    placeholder="Describe what products belong in this category..." />

                <flux:select label="Parent Category" wire:model="form.parent_id">
                    <flux:select.option value="">Root (No Parent)</flux:select.option>
                    @foreach ($this->parents as $parent)
                        <flux:select.option value="{{ $parent->id }}">{{ $parent->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>


        {{-- Placements --}}
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">Placements</flux:heading>
            </div>
            <div class="p-5 space-y-3">
                <flux:description class="mb-3">
                    Select where this category should appear on the storefront.
                </flux:description>

                @foreach (\App\Enums\CategorySection::cases() as $section)
                    <flux:field class="flex! items-center! gap-3">
                        <flux:checkbox wire:model="form.placements" value="{{ $section->value }}"
                            id="placement_{{ $section->value }}" />
                        <flux:label for="placement_{{ $section->value }}" class="font-normal cursor-pointer">
                            {{ $section->label() }}
                        </flux:label>
                    </flux:field>
                @endforeach
            </div>
        </flux:card>

        {{-- SEO Metadata --}}
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">SEO Metadata</flux:heading>
            </div>

            <div class="p-5 space-y-5">
                <flux:input label="Meta Title" wire:model="form.meta_title"
                    placeholder="Leave blank to use category name" />
                <flux:textarea label="Meta Description" wire:model="form.meta_description"
                    placeholder="Brief description for search engines..." rows="3" />
            </div>
        </flux:card>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Status --}}
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">Status</flux:heading>
            </div>
            <div class="p-5">
                <flux:select wire:model="form.status">
                    @foreach (\App\Enums\CategoryStatus::cases() as $status)
                        <flux:select.option value="{{ $status->value }}">
                            {{ $status->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>


        {{-- Icons & Media --}}
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">Icons & Media</flux:heading>
            </div>
            <div class="p-5 space-y-5" x-data>

                {{-- Category Icon --}}
                <flux:field>
                    <flux:label>Category Icon</flux:label>
                    <div class="flex items-center gap-3 mt-1">
                        <div
                            class="shrink-0 w-16 h-16 rounded border bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 flex items-center justify-center overflow-hidden">
                            @if ($form->image_icon)
                                <img src="{{ $form->image_icon->temporaryUrl() }}"
                                    class="w-full h-full object-contain p-1" />
                            @elseif ($form->existingImageIcon)
                                <img src="{{ Storage::url($form->existingImageIcon) }}"
                                    class="w-full h-full object-contain p-1" />
                            @else
                                <flux:icon.photo class="size-8 text-zinc-300 stroke-1! dark:text-zinc-600" />
                            @endif
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500 mb-2">Recommended: 64px × 64px</flux:text>
                            <div class="flex items-center gap-2">
                                <input type="file" wire:model="form.image_icon" x-ref="icon_input" class="sr-only"
                                    accept="image/*" />
                                <flux:button type="button" size="xs" variant="primary"
                                    x-on:click="$refs.icon_input.click()" class="cursor-pointer">
                                    {{ $form->existingImageIcon ? 'Change' : 'Upload' }}
                                </flux:button>
                                @if ($form->existingImageIcon)
                                    <flux:button type="button" size="xs"
                                        wire:click="$set('form.existingImageIcon', null)" class="cursor-pointer">
                                        Remove
                                    </flux:button>
                                @elseif ($form->image_icon)
                                    <flux:button type="button" size="xs"
                                        wire:click="$set('form.image_icon', null)" class="cursor-pointer">
                                        Cancel
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <flux:error name="form.image_icon" />
                </flux:field>

                <flux:separator variant="subtle" />

                {{-- Icon SVG --}}
                <flux:field>
                    <flux:textarea label="Icon SVG Code" wire:model.live="form.icon_svg" placeholder="<svg>...</svg>"
                        rows="3" />
                    @if ($form->icon_svg)
                        <div class="mt-2 p-2 border rounded bg-zinc-50 w-12 h-12 flex items-center justify-center">
                            {!! $form->icon_svg !!}
                        </div>
                    @endif
                    <flux:error name="form.icon_svg" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Category Banner --}}
        <flux:card class="p-0">
            <div class="px-3 py-2 border-b dark:border-b-zinc-600">
                <flux:heading size="lg">Category Banner</flux:heading>
            </div>
            <div class="p-5 space-y-3" x-data>
                @if ($form->image_path)
                    <img src="{{ $form->image_path->temporaryUrl() }}"
                        class="w-full aspect-video rounded border dark:border-zinc-600 object-cover" />
                @elseif ($form->existingBanner)
                    <img src="{{ Storage::url($form->existingBanner) }}"
                        class="w-full aspect-video rounded border dark:border-zinc-600 object-cover" />
                @else
                    <div
                        class="w-full aspect-video rounded border dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 flex items-center justify-center">
                        <flux:icon.photo class="size-24 text-zinc-300 stroke-1! dark:text-zinc-600" />
                    </div>
                @endif

                <input type="file" wire:model="form.image_path" x-ref="banner_input" class="sr-only"
                    accept="image/*" />

                <div class="flex items-center gap-2">
                    <flux:button type="button" size="xs" variant="primary"
                        x-on:click="$refs.banner_input.click()" class="cursor-pointer">
                        {{ $form->existingBanner ? 'Change Banner' : 'Upload Banner' }}
                    </flux:button>
                    @if ($form->existingBanner)
                        <flux:button type="button" size="xs" wire:click="$set('form.existingBanner', null)"
                            class="cursor-pointer">
                            Remove
                        </flux:button>
                    @elseif ($form->image_path)
                        <flux:button type="button" size="xs" wire:click="$set('form.image_path', null)"
                            class="cursor-pointer">
                            Cancel
                        </flux:button>
                    @endif
                </div>
                <flux:text class="text-xs text-zinc-500">Recommended: 1200px × 400px (max 2MB)</flux:text>
                <flux:error name="form.image_path" />
            </div>
        </flux:card>

    </div>
</div>
