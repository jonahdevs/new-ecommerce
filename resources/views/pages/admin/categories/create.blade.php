<?php

use App\Enums\CategoryStatus;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('New Category — Admin')] class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $slug = '';

    public ?int $parent_id = null;

    public string $description = '';

    public string $status = 'draft';

    public int $sort_order = 0;

    public string $icon_svg = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $canonical_url = '';

    public $pendingImage = null;

    public $pendingThumbnail = null;

    public $pendingIcon = null;

    private bool $slugManuallyEdited = false;

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    #[Computed]
    public function parentOptions()
    {
        return Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'status' => ['required', Rule::in(array_column(CategoryStatus::cases(), 'value'))],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'pendingImage' => ['nullable', 'image', 'max:4096'],
            'pendingThumbnail' => ['nullable', 'image', 'max:2048'],
            'pendingIcon' => ['nullable', 'image', 'max:1024'],
        ]);

        $category = Category::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id ?: null,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'icon_svg' => $this->icon_svg ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'canonical_url' => $this->canonical_url ?: null,
        ]);

        $this->attachMedia($category, 'pendingImage', 'banner');
        $this->attachMedia($category, 'pendingThumbnail', 'square');
        $this->attachMedia($category, 'pendingIcon', 'icon');

        Flux::toast(heading: 'Category created', text: $this->name.' has been added.', variant: 'success');
        $this->redirectRoute('admin.categories.edit', $category, navigate: true);
    }

    /**
     * Move a freshly uploaded temporary file into its single-file media collection.
     */
    private function attachMedia(Category $category, string $pendingProperty, string $collection): void
    {
        if (! $this->{$pendingProperty}) {
            return;
        }

        $category
            ->addMedia($this->{$pendingProperty}->getRealPath())
            ->usingFileName($this->{$pendingProperty}->getClientOriginalName())
            ->toMediaCollection($collection);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New category</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">New category</flux:heading>
                <flux:subheading>Add a new category to organise your product catalog.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.categories.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="plus">Create category</flux:button>
            </div>
        </div>

        {{-- Two-column layout --}}
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main column --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Basic info --}}
                <div x-data="{ open: true }"
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-3"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base" class="uppercase tracking-wide">Basic information</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:input wire:model.live.debounce.400ms="name" label="Name"
                                placeholder="e.g. Cooking Ranges" required autofocus />
                            <flux:input wire:model.blur="slug" label="Slug" />
                            <flux:textarea wire:model="description" label="Description" rows="3"
                                placeholder="Brief description shown on the category page…" />
                        </div>
                    </div>
                </div>

                {{-- Banner image --}}
                <flux:card x-data="{ open: true }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-3"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base" class="uppercase tracking-wide">Banner image</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="p-6">
                            @if ($pendingImage)
                                <div class="group relative overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $pendingImage->temporaryUrl() }}" alt="Preview"
                                        class="h-44 w-full object-cover" />
                                    <button type="button" wire:click="$set('pendingImage', null)"
                                        class="absolute right-2 top-2 rounded-full bg-white/90 p-1 shadow hover:bg-white dark:bg-zinc-900/90">
                                        <flux:icon.x-mark variant="micro" class="size-4 text-zinc-600" />
                                    </button>
                                </div>
                            @else
                                <label
                                    class="flex h-44 cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-zinc-300 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                                    <flux:icon.arrow-up-tray class="size-6 text-zinc-400" />
                                    <flux:text size="sm" class="text-zinc-400">Click to upload banner</flux:text>
                                    <input type="file" wire:model="pendingImage" accept="image/*" class="sr-only" />
                                </label>
                            @endif
                            <div wire:loading wire:target="pendingImage" class="mt-2 text-xs text-zinc-400">Uploading…</div>
                            @error('pendingImage') <flux:error class="mt-1">{{ $message }}</flux:error> @enderror
                        </div>
                    </div>
                </flux:card>

                {{-- SEO --}}
                <div x-data="{ open: false }"
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-3"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base" class="uppercase tracking-wide">SEO</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:input wire:model="meta_title" label="Meta title"
                                placeholder="Defaults to category name" />
                            <flux:textarea wire:model="meta_description" label="Meta description" rows="2"
                                placeholder="Short description for search engines…" />
                            <flux:input wire:model="canonical_url" label="Canonical URL" type="url"
                                placeholder="https://" />
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">

                {{-- Status & hierarchy --}}
                <flux:card x-data="{ open: true }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-3"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm" class="uppercase tracking-wide">Status & hierarchy</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:select wire:model="status" label="Status">
                                @foreach (CategoryStatus::cases() as $s)
                                    <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="parent_id" label="Parent category">
                                <flux:select.option value="">No parent (top-level)</flux:select.option>
                                @foreach ($this->parentOptions as $opt)
                                    <flux:select.option :value="$opt->id">{{ $opt->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
                        </div>
                    </div>
                </flux:card>

                {{-- Image (primary square image — grid tiles & menus) --}}
                <flux:card x-data="{ open: true }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-3"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm" class="uppercase tracking-wide">Image</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="p-6">
                            @if ($pendingThumbnail)
                                <div class="group relative inline-block overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $pendingThumbnail->temporaryUrl() }}" alt="Preview"
                                        class="size-24 object-cover" />
                                    <button type="button" wire:click="$set('pendingThumbnail', null)"
                                        class="absolute right-1 top-1 rounded-full bg-white/90 p-0.5 shadow hover:bg-white dark:bg-zinc-900/90">
                                        <flux:icon.x-mark variant="micro" class="size-3 text-zinc-600" />
                                    </button>
                                </div>
                            @else
                                <label
                                    class="flex h-24 w-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed border-zinc-300 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                                    <flux:icon.arrow-up-tray class="size-5 text-zinc-400" />
                                    <flux:text size="sm" class="text-center text-[11px] text-zinc-400">Upload</flux:text>
                                    <input type="file" wire:model="pendingThumbnail" accept="image/*" class="sr-only" />
                                </label>
                            @endif
                            <div wire:loading wire:target="pendingThumbnail" class="mt-2 text-xs text-zinc-400">Uploading…</div>
                            @error('pendingThumbnail') <flux:error class="mt-1">{{ $message }}</flux:error> @enderror
                            <flux:text size="sm" class="mt-3 text-zinc-500">Primary square image shown in listings and menus.</flux:text>
                        </div>
                    </div>
                </flux:card>

                {{-- Icon --}}
                <flux:card x-data="{ open: true }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-3"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm" class="uppercase tracking-wide">Icon</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            @if ($pendingIcon)
                                <div class="group relative inline-block overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $pendingIcon->temporaryUrl() }}" alt="Preview"
                                        class="size-16 object-contain p-1" />
                                    <button type="button" wire:click="$set('pendingIcon', null)"
                                        class="absolute right-1 top-1 rounded-full bg-white/90 p-0.5 shadow hover:bg-white dark:bg-zinc-900/90">
                                        <flux:icon.x-mark variant="micro" class="size-3 text-zinc-600" />
                                    </button>
                                </div>
                            @else
                                <label
                                    class="flex h-16 w-16 cursor-pointer flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed border-zinc-300 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                                    <flux:icon.arrow-up-tray class="size-4 text-zinc-400" />
                                    <input type="file" wire:model="pendingIcon" accept="image/*" class="sr-only" />
                                </label>
                            @endif
                            <div wire:loading wire:target="pendingIcon" class="text-xs text-zinc-400">Uploading…</div>
                            @error('pendingIcon') <flux:error class="mt-1">{{ $message }}</flux:error> @enderror
                            <flux:textarea wire:model="icon_svg" label="Icon SVG" rows="3"
                                placeholder="<svg>…</svg>"
                                description="Paste raw SVG markup. Used as fallback if no icon image is set." />
                        </div>
                    </div>
                </flux:card>

            </div>
        </div>
    </form>
</div>
