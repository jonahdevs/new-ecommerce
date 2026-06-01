<?php

use App\Models\Page;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Page — Admin')] class extends Component {
    public ?int $pageId = null;

    public string $title = '';

    public string $slug = '';

    public string $body = '';

    public string $meta_description = '';

    public bool $is_published = true;

    private bool $slugManuallyEdited = false;

    public function mount(?Page $page = null): void
    {
        if ($page?->exists) {
            $this->pageId = $page->id;
            $this->title = $page->title;
            $this->slug = $page->slug;
            $this->body = (string) $page->body;
            $this->meta_description = (string) $page->meta_description;
            $this->is_published = $page->is_published;
            $this->slugManuallyEdited = true;
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages', 'slug')->ignore($this->pageId)],
            'body' => ['nullable', 'string', 'max:65535'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ]);

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body ?: null,
            'meta_description' => $this->meta_description ?: null,
            'is_published' => $this->is_published,
        ];

        if ($this->pageId) {
            Page::findOrFail($this->pageId)->update($data);
        } else {
            Page::create($data);
        }

        Flux::toast(heading: 'Saved', text: $this->title.' has been saved.', variant: 'success');

        $this->redirectRoute('admin.pages.index', navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.pages.index')" wire:navigate>Pages</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $pageId ? $title : 'New page' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $pageId ? 'Edit page' : 'New page' }}</flux:heading>
                <flux:subheading>{{ $pageId ? 'Update this content page.' : 'Add a new content page.' }}</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                @if ($pageId)
                    <flux:button variant="ghost" icon="arrow-top-right-on-square"
                        :href="route('page.show', $slug)" target="_blank">View</flux:button>
                @endif
                <flux:button variant="ghost" :href="route('admin.pages.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">{{ $pageId ? 'Save changes' : 'Add page' }}</flux:button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main column --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="space-y-5">
                    <flux:input wire:model.live.debounce.400ms="title" label="Title" placeholder="e.g. Privacy Policy" required />
                    <flux:input wire:model.blur="slug" label="Slug"
                        description="Auto-generated from the title. Used in the page URL." />
                    <flux:textarea wire:model="body" label="Content" rows="20"
                        description="Markdown supported — headings, lists, links." />
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <flux:card class="space-y-4">
                    <flux:heading size="sm">Visibility</flux:heading>
                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <div>
                            <flux:label>Published</flux:label>
                            <flux:text size="sm" class="text-xs">Drafts are hidden from the storefront (404).</flux:text>
                        </div>
                        <flux:switch wire:model="is_published" />
                    </div>
                </flux:card>

                <flux:card class="space-y-4">
                    <flux:heading size="sm">SEO</flux:heading>
                    <flux:textarea wire:model="meta_description" label="Meta description" rows="3"
                        placeholder="Shown in search results." />
                </flux:card>
            </div>
        </div>
    </form>
</div>
