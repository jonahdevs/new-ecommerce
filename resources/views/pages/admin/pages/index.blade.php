<?php

use App\Models\Page;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Pages | Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pages()
    {
        return Page::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('is_published', $this->filterStatus === 'published'))
            ->orderBy('title')
            ->paginate($this->perPage);
    }

    public function togglePublished(int $id): void
    {
        $page = Page::findOrFail($id);
        $page->update(['is_published' => ! $page->is_published]);
        unset($this->pages);
    }

    public function delete(int $id): void
    {
        Page::findOrFail($id)->delete();
        unset($this->pages);
        Flux::toast(heading: 'Page deleted', text: 'The page has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Pages</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Pages</flux:heading>
            <flux:subheading>Content pages — legal policies and other static pages linked in the footer.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate>Add page</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="published">Published</flux:select.option>
                    <flux:select.option value="draft">Draft</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Updated</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->pages as $page)
                    <flux:table.row :key="$page->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="hover:text-brand-500">
                                {{ $page->title }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-400">/{{ $page->slug }}</flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="togglePublished({{ $page->id }})">
                                <flux:badge size="sm" inset="top bottom" :color="$page->is_published ? 'green' : 'zinc'">
                                    {{ $page->is_published ? 'Published' : 'Draft' }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $page->updated_at->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown align="end">
                                <flux:button size="sm" icon-trailing="chevron-down">Actions</flux:button>
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square"
                                        :href="route('admin.pages.edit', $page)" wire:navigate>
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item icon="arrow-top-right-on-square"
                                        :href="route('page.show', $page->slug)" target="_blank">
                                        View page
                                    </flux:menu.item>
                                    <flux:menu.item icon="clock"
                                        :href="route('admin.activity.item', ['page', $page->id])"
                                        wire:navigate>
                                        Activity log
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash-2" variant="danger"
                                        wire:click="delete({{ $page->id }})"
                                        wire:confirm="Delete '{{ addslashes($page->title) }}'? This cannot be undone.">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
                            No pages found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->pages->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->pages" />
            </div>
        @endif
    </flux:card>
</div>
