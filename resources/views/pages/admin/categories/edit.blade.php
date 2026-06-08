<?php

use App\Enums\CategoryStatus;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Edit Category — Admin')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public Category $category;

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

    public ?string $image = null;

    public ?string $thumbnail = null;

    public ?string $icon = null;

    public $pendingImage = null;

    public $pendingThumbnail = null;

    public $pendingIcon = null;

    private bool $slugManuallyEdited = false;

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->parent_id = $category->parent_id;
        $this->description = (string) $category->description;
        $this->status = $category->status->value;
        $this->sort_order = (int) $category->sort_order;
        $this->icon_svg = (string) $category->icon_svg;
        $this->meta_title = (string) $category->meta_title;
        $this->meta_description = (string) $category->meta_description;
        $this->canonical_url = (string) $category->canonical_url;
        $this->image = $category->image;
        $this->thumbnail = $category->thumbnail;
        $this->icon = $category->icon;
        $this->slugManuallyEdited = true;
    }

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
        return Category::whereNull('parent_id')
            ->where('id', '!=', $this->category->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($this->category->id)],
            'status' => ['required', Rule::in(array_column(CategoryStatus::cases(), 'value'))],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'pendingImage' => ['nullable', 'image', 'max:4096'],
            'pendingThumbnail' => ['nullable', 'image', 'max:2048'],
            'pendingIcon' => ['nullable', 'image', 'max:1024'],
        ]);

        if ($this->pendingImage) {
            if ($this->image) {
                Storage::disk('public')->delete($this->image);
            }
            $this->image = $this->pendingImage->store('categories', 'public');
            $this->pendingImage = null;
        }

        if ($this->pendingThumbnail) {
            if ($this->thumbnail) {
                Storage::disk('public')->delete($this->thumbnail);
            }
            $this->thumbnail = $this->pendingThumbnail->store('categories', 'public');
            $this->pendingThumbnail = null;
        }

        if ($this->pendingIcon) {
            if ($this->icon) {
                Storage::disk('public')->delete($this->icon);
            }
            $this->icon = $this->pendingIcon->store('categories', 'public');
            $this->pendingIcon = null;
        }

        $this->category->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id ?: null,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'image' => $this->image,
            'thumbnail' => $this->thumbnail,
            'icon' => $this->icon,
            'icon_svg' => $this->icon_svg ?: null,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'canonical_url' => $this->canonical_url ?: null,
        ]);

        Flux::toast(heading: 'Category saved', text: $this->name.' has been updated.', variant: 'success');
    }

    public function removeImage(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }
        $this->image = null;
        $this->pendingImage = null;
        $this->category->update(['image' => null]);
    }

    public function removeThumbnail(): void
    {
        if ($this->thumbnail) {
            Storage::disk('public')->delete($this->thumbnail);
        }
        $this->thumbnail = null;
        $this->pendingThumbnail = null;
        $this->category->update(['thumbnail' => null]);
    }

    public function removeIcon(): void
    {
        if ($this->icon) {
            Storage::disk('public')->delete($this->icon);
        }
        $this->icon = null;
        $this->pendingIcon = null;
        $this->category->update(['icon' => null]);
    }

    public function getImagePreview(): ?string
    {
        if ($this->pendingImage) {
            return $this->pendingImage->temporaryUrl();
        }

        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getThumbnailPreview(): ?string
    {
        if ($this->pendingThumbnail) {
            return $this->pendingThumbnail->temporaryUrl();
        }

        return $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null;
    }

    public function getIconPreview(): ?string
    {
        if ($this->pendingIcon) {
            return $this->pendingIcon->temporaryUrl();
        }

        return $this->icon ? Storage::disk('public')->url($this->icon) : null;
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.categories.index')" wire:navigate>Categories</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $category->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $category->name }}</flux:heading>
                <flux:subheading>Edit category details, images, and SEO.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.activity.item', ['category', $category->id])"
                    icon="clock" wire:navigate>
                    Activity log
                </flux:button>
                <flux:button variant="ghost" :href="route('admin.categories.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
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
                        class="flex w-full items-center justify-between px-6 py-4"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base">Basic information</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            <flux:input wire:model.live.debounce.400ms="name" label="Name" required autofocus />
                            <flux:input wire:model.blur="slug" label="Slug"
                                description="Auto-generated from name. Used in URLs." />
                            <flux:textarea wire:model="description" label="Description" rows="3"
                                placeholder="Brief description shown on the category page…" />
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <div x-data="{ open: {{ ($meta_title || $meta_description || $canonical_url) ? 'true' : 'false' }} }"
                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-4"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="base">SEO</flux:heading>
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
                        class="flex w-full items-center justify-between px-6 py-4"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <div class="flex items-center gap-3">
                            <flux:heading size="sm">Status & hierarchy</flux:heading>
                            <flux:badge size="sm" :color="$category->status->color()">
                                {{ $category->status->label() }}
                            </flux:badge>
                        </div>
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
                            <flux:input wire:model="sort_order" label="Sort order" type="number" min="0"
                                description="Lower number = displayed first." />
                        </div>
                    </div>
                </flux:card>

                {{-- Banner image --}}
                <flux:card x-data="{ open: true }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-4"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Banner image</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="p-6">
                            @if ($this->getImagePreview())
                                <div class="group relative overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $this->getImagePreview() }}" alt="Banner"
                                        class="h-36 w-full object-cover" />
                                    <button type="button"
                                        wire:click="{{ $pendingImage ? '$set(\'pendingImage\', null)' : 'removeImage' }}"
                                        class="absolute right-2 top-2 rounded-full bg-white/90 p-1 shadow hover:bg-white dark:bg-zinc-900/90">
                                        <flux:icon.x-mark variant="micro" class="size-4 text-zinc-600" />
                                    </button>
                                </div>
                            @else
                                <label
                                    class="flex h-36 cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-zinc-300 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                                    <flux:icon.arrow-up-tray class="size-6 text-zinc-400" />
                                    <flux:text size="sm" class="text-zinc-400">Click to upload banner</flux:text>
                                    <input type="file" wire:model="pendingImage" accept="image/*" class="sr-only" />
                                </label>
                            @endif
                            @if ($this->getImagePreview() && ! $pendingImage)
                                <label class="mt-3 flex cursor-pointer items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-600">
                                    <flux:icon.arrow-up-tray variant="micro" class="size-3" />
                                    Replace image
                                    <input type="file" wire:model="pendingImage" accept="image/*" class="sr-only" />
                                </label>
                            @endif
                            <div wire:loading wire:target="pendingImage" class="mt-2 text-xs text-zinc-400">Uploading…</div>
                            @error('pendingImage') <flux:error class="mt-1">{{ $message }}</flux:error> @enderror
                        </div>
                    </div>
                </flux:card>

                {{-- Thumbnail --}}
                <flux:card x-data="{ open: {{ $thumbnail ? 'true' : 'false' }} }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-4"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Thumbnail</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="p-6">
                            @if ($this->getThumbnailPreview())
                                <div class="group relative inline-block overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $this->getThumbnailPreview() }}" alt="Thumbnail"
                                        class="size-24 object-cover" />
                                    <button type="button"
                                        wire:click="{{ $pendingThumbnail ? '$set(\'pendingThumbnail\', null)' : 'removeThumbnail' }}"
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
                            @if ($this->getThumbnailPreview() && ! $pendingThumbnail)
                                <label class="mt-3 flex cursor-pointer items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-600">
                                    <flux:icon.arrow-up-tray variant="micro" class="size-3" />
                                    Replace
                                    <input type="file" wire:model="pendingThumbnail" accept="image/*" class="sr-only" />
                                </label>
                            @endif
                            <div wire:loading wire:target="pendingThumbnail" class="mt-2 text-xs text-zinc-400">Uploading…</div>
                            @error('pendingThumbnail') <flux:error class="mt-1">{{ $message }}</flux:error> @enderror
                            <flux:text size="sm" class="mt-3 text-zinc-500">Small square image for listings and menus.</flux:text>
                        </div>
                    </div>
                </flux:card>

                {{-- Icon --}}
                <flux:card x-data="{ open: {{ ($icon || $icon_svg) ? 'true' : 'false' }} }" class="overflow-hidden p-0">
                    <button type="button" x-on:click="open = !open"
                        class="flex w-full items-center justify-between px-6 py-4"
                        :class="open ? 'border-b border-zinc-200 dark:border-zinc-700' : ''">
                        <flux:heading size="sm">Icon</flux:heading>
                        <span class="inline-flex transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <flux:icon.chevron-down variant="micro" class="size-4 text-zinc-400" />
                        </span>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="space-y-4 p-6">
                            @if ($this->getIconPreview())
                                <div class="group relative inline-block overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $this->getIconPreview() }}" alt="Icon"
                                        class="size-16 object-contain p-1" />
                                    <button type="button"
                                        wire:click="{{ $pendingIcon ? '$set(\'pendingIcon\', null)' : 'removeIcon' }}"
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
                            @if ($this->getIconPreview() && ! $pendingIcon)
                                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-600">
                                    <flux:icon.arrow-up-tray variant="micro" class="size-3" />
                                    Replace
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
