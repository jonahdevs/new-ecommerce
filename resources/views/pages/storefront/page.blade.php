<?php

use App\Models\Page;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::storefront')] class extends Component
{
    public Page $page;

    public function mount(Page $page): void
    {
        abort_unless($page->is_published, 404);

        $this->page = $page;

        SEOMeta::setTitle($page->title.' — Sheffield');

        if (filled($page->meta_description)) {
            SEOMeta::setDescription($page->meta_description);
        }
    }
}; ?>

<div class="page-fade">
    <div class="shell pt-4 pb-20">
        {{-- Breadcrumb --}}
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $page->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <h1 class="text-3xl font-semibold tracking-tight">{{ $page->title }}</h1>

        <div class="mt-6 max-w-3xl space-y-4 text-[14.5px] leading-relaxed text-ink-2
                    [&_a]:text-brand-500 [&_a]:underline
                    [&_h2]:mt-8 [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:text-ink
                    [&_h3]:mt-6 [&_h3]:font-semibold [&_h3]:text-ink
                    [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-6
                    [&_ol]:list-decimal [&_ol]:space-y-1 [&_ol]:pl-6">
            {!! Str::markdown($page->body ?? '') !!}
        </div>
    </div>
</div>
