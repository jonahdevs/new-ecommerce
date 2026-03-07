<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Status & Visibility</flux:heading>

        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5 space-y-5">
        {{-- Publication Status --}}

        <flux:select wire:model="form.status" label="Publication Status">
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="scheduled">Scheduled</flux:select.option>
            <flux:select.option value="published">Published</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
        @php
            $config1 = [
                'minDate' => now()->addDay()->format('Y-m-d'),
            ];
        @endphp

        <div wire:show="form.status === 'scheduled'" wire:cloak>
            <x-my-datepicker wire:model="form.published_at" icon="o-calendar" :config="$config1" />
        </div>

        <flux:separator />

        {{-- Visibility Options --}}
        <div class="pt-2 space-y-3 ">
            <flux:field variant="inline">
                <flux:checkbox wire:model="form.is_featured" />
                <flux:label class="flex flex-col items-start">
                    <p> Featured</p>
                    <p class="text-xs text-zinc-500">Show in featured section</p>
                </flux:label>
                <flux:error name="terms" />
            </flux:field>
        </div>
    </div>
</flux:card>

{{-- BRAND CARD --}}
<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Product Brand</flux:heading>

        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5 space-y-5" :class="{ '-mb-5': !$wire.addNewBrand }">
        {{-- Brand Select --}}
        <x-my-choices-offline wire:model="form.brand_id" :options="$this->brands" single clearable searchable />

        {{-- Add New Brand Toggle --}}
        <flux:button @click="$wire.addNewBrand = !$wire.addNewBrand" type="button" icon="plus" variant="ghost"
            size="xs" class="underline cursor-pointer">
            {{ $addNewBrand ? 'Cancel' : 'Add new brand' }}
        </flux:button>

        {{-- Add New Brand Form --}}
        <div wire:show="addNewBrand" wire:cloak class="space-y-5">
            <flux:input wire:model="form.newBrandName" label="Brand Name" placeholder="Enter brand name" />

            <flux:input wire:model="form.newBrandWebsite" label="Website (Optional)" placeholder="https://example.com"
                type="url" />

            <div class="flex gap-2">
                <flux:button type="button" wire:click="createBrand" class="flex-1">
                    Create Brand
                </flux:button>

                <flux:button type="button" wire:click="cancelBrandCreation" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </div>
    </div>
</flux:card>

<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Product Image</flux:heading>
        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div class="p-5" x-show="expanded" x-cloak x-collapse>
        <input type="file" class="hidden" id="product-image-input" wire:model="form.image" />

        {{-- Loading indicator --}}
        <flux:icon.loading wire:loading wire:target="form.image" />

        {{-- Content — hidden while uploading --}}
        <div wire:loading.remove wire:target="form.image">
            @if ($form->image)
                <div class="space-y-3">
                    <div @click="document.getElementById('product-image-input').click()"
                        class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden cursor-pointer">
                        <img src="{{ $form->image->temporaryUrl() }}" alt="Product Image preview"
                            class="w-full h-full object-cover">
                        @if (!empty($form->product))
                            <div class="absolute top-0 left-0">
                                <flux:badge color="green" size="sm" variant="solid"
                                    class="rounded-tl-md rounded-br-md rounded-none">New
                                </flux:badge>
                            </div>
                        @endif
                    </div>
                    <flux:text class="text-xs">Click the image to change</flux:text>
                    <flux:link wire:click="$set('form.image', null)" class="text-sm text-red-500 cursor-pointer">
                        Remove Image
                    </flux:link>
                </div>
            @elseif ($form->existing_image)
                <div class="space-y-3">
                    <div @click="document.getElementById('product-image-input').click()"
                        class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden border-2 border-zinc-200 cursor-pointer group">
                        <img src="{{ Storage::url($form->existing_image) }}" alt="Current product image"
                            class="w-full h-full object-cover">
                        <div
                            class="absolute inset-0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
                            <flux:text class="opacity-0 group-hover:opacity-100 text-white font-semibold">
                                Click to change
                            </flux:text>
                        </div>
                    </div>
                    <flux:text class="text-xs">Click the image to update</flux:text>
                </div>
            @else
                <flux:link @click="document.getElementById('product-image-input').click()"
                    class="text-sm text-sheffield-blue cursor-pointer">
                    Set product image
                </flux:link>
            @endif

            <flux:error name="form.image" />
        </div>
    </div>
</flux:card>

<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Product Gallery</flux:heading>
        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5">
        <input type="file" class="hidden" id="product-gallery-input" wire:model="form.images" multiple />

        {{-- Content — hidden while uploading --}}
        <div>
            @if (!empty($form->images) || !empty($form->existingImages))
                <div class="grid grid-cols-3 gap-3 mb-3">

                    {{-- Existing images --}}
                    @foreach ($form->existingImages as $index => $existingImage)
                        <div class="relative group">
                            <div
                                class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden border-2 border-zinc-200">
                                <img src="{{ $existingImage->url }}" alt="Gallery image"
                                    class="w-full h-full object-cover">
                                <div
                                    class="absolute inset-0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
                                    <button type="button"
                                        wire:click="form.removeGalleryImage('{{ $existingImage }}')"
                                        wire:confirm="Are you sure you want to remove this image?"
                                        class="opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity duration-200 bg-red-500 hover:bg-red-600 text-white rounded-full p-2">
                                        <flux:icon.trash variant="micro" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- New images --}}
                    @foreach ($form->images as $index => $img)
                        <div class="relative group">
                            <div class="relative mx-auto w-full aspect-square rounded-sm overflow-hidden">
                                <img src="{{ $img->temporaryUrl() }}" alt="New gallery image"
                                    class="w-full h-full object-cover">

                                @if (!empty($form->product))
                                    <div class="absolute top-0 left-0">
                                        <flux:badge color="green" size="sm" variant="solid"
                                            class="rounded-tl-md rounded-br-md rounded-none">New
                                        </flux:badge>
                                    </div>
                                @endif
                                <div
                                    class="absolute inset-0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
                                    <button type="button"
                                        wire:click="$set('form.images', {{ json_encode(array_values(array_filter($form->images, fn($key) => $key !== $index, ARRAY_FILTER_USE_KEY))) }})"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 cursor-pointer">
                                        <flux:icon.trash variant="micro" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:text class="text-xs text-zinc-500 mb-3">
                    {{ count($form->existingImages) }} existing, {{ count($form->images) }} new to upload
                </flux:text>
            @endif

            <flux:link @click="document.getElementById('product-gallery-input').click()"
                class="text-sm text-sheffield-blue cursor-pointer">
                {{ !empty($form->images) || !empty($form->existingImages) ? 'Add more images' : 'Set product gallery images' }}
            </flux:link>

            @if (!empty($form->imagesToDelete))
                <flux:text class="text-xs text-red-500 mt-2">
                    {{ count($form->imagesToDelete) }} image(s) will be deleted when you save
                </flux:text>
            @endif

            <flux:error name="form.images" />
        </div>

        {{-- Loading indicator --}}
        <flux:icon.loading wire:loading wire:target="form.images" class="mt-3" />
    </div>
</flux:card>

<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Product Categories</flux:heading>
        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5 space-y-5" :class="{ '-mb-5': !$wire.addNewCategory }"
        x-data="{ search: '' }">

        {{-- Search --}}
        <flux:input x-model="search" placeholder="Search categories..." icon="magnifying-glass" clearable
            size="sm" />

        {{-- Categories List --}}
        <div class="max-h-64 overflow-y-auto border-2 dark:border-zinc-600 rounded-md"
            wire:key="categories-{{ md5(json_encode($form->category_ids)) }}">
            <div class="">
                @foreach ($this->categories as $category)
                    <div class="flex items-center gap-2 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
                        wire:key="cat-{{ $category['id'] }}"
                        x-show="search === '' || '{{ strtolower($category['name']) }}'.includes(search.toLowerCase())">

                        @if ($category['depth'] > 0)
                            <span class="shrink-0" style="padding-left: {{ $category['depth'] * 12 }}px">
                                <flux:icon.chevron-right variant="micro" class="text-zinc-400 size-3" />
                            </span>
                        @endif

                        <flux:checkbox wire:model.live="form.category_ids" :value="$category['id']"
                            :label="$category['name']" />
                    </div>
                @endforeach
            </div>
        </div>

        {{-- No results — outside the list, checks all category names at once --}}
        <div class="text-sm text-zinc-400 text-center py-2" x-show="search !== ''" x-data="{
            get noResults() {
                const names = @js(collect($this->categories)->pluck('name')->map(fn($n) => strtolower($n))->values()->toArray());
                return !names.some(name => name.includes(search.toLowerCase()));
            }
        }">
            <template x-if="noResults">
                <span>No categories match "<span x-text="search"></span>"</span>
            </template>
        </div>

        {{-- Selected count --}}
        @if (!empty($form->category_ids))
            <flux:text class="text-xs text-zinc-500">
                {{ count($form->category_ids) }} categor{{ count($form->category_ids) === 1 ? 'y' : 'ies' }} selected
            </flux:text>
        @endif

        {{-- Add New Category Toggle --}}
        <flux:button @click="$wire.addNewCategory = !$wire.addNewCategory" type="button" icon="plus"
            variant="ghost" size="xs" class="underline cursor-pointer">
            {{ $addNewCategory ? 'Cancel' : 'Add new category' }}
        </flux:button>

        {{-- Add New Category Form --}}
        <div wire:show="addNewCategory" wire:cloak class="space-y-5">
            <flux:input wire:model="form.newCategoryName" placeholder="Enter category name" />

            <flux:select wire:model="form.newCategoryParentId" placeholder="-- Parent Category --">
                @foreach ($this->allCategories as $category)
                    <flux:select.option :value="$category->id">
                        {{ $category->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:button type="button" wire:click="createCategory" size="sm">
                Add new category
            </flux:button>
        </div>
    </div>
</flux:card>

<flux:card class="p-0" x-data="{ expanded: true }">
    <div class="border-b px-3 py-2 flex items-center justify-between dark:border-zinc-600"
        :class="{ 'border-b ': expanded }">
        <flux:heading>Product Tag</flux:heading>

        <flux:button icon="chevron-down" size="xs" variant="ghost"
            class="cursor-pointer transition-transform duration-300" x-bind:class="{ 'rotate-180': expanded }"
            @click="expanded = !expanded" />
    </div>

    <div x-show="expanded" x-cloak x-collapse class="p-5 space-y-5">
        <flux:input.group>
            <flux:input wire:model="form.newTagInput" />
            <flux:button type="button" wire:click="addTags" class="cursor-pointer">Add</flux:button>
        </flux:input.group>

        <flux:text>Seperate tags with commas</flux:text>

        {{-- Selected Tags Display --}}
        @if ($this->selectedTags->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($this->selectedTags as $tag)
                    <flux:badge color="zinc" class="flex items-center gap-1.5" size="sm">
                        <span>{{ $tag->name }}</span>
                        <button type="button" wire:click="removeTag({{ $tag->id }})"
                            class="hover:text-red-600 transition-colors cursor-pointer">
                            <flux:icon.x-mark variant="micro" />
                        </button>
                    </flux:badge>
                @endforeach
            </div>
        @endif

        {{-- Most Used Tags Button --}}
        <flux:button type="button" wire:click="openTagModal" class="cursor-pointer" variant="ghost"
            size="xs">
            Choose from the most used tags
        </flux:button>
    </div>
</flux:card>
