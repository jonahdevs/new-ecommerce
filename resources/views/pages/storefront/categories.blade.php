<?php

use App\Enums\CategoryStatus;
use App\Models\Category;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('All Categories')] class extends Component
{
    public function mount(): void
    {
        $description = 'Browse Sheffield Africa\'s full range of commercial equipment — kitchen, cold room, laundry and healthcare. Find the right category and explore our products.';

        SEOMeta::setTitle('All Categories — Sheffield Africa')->setDescription($description);
        OpenGraph::setTitle('All Categories')->setDescription($description)->setType('website');
        TwitterCard::setDescription($description);
        JsonLdMulti::setType('CollectionPage')->setTitle('All Categories')->setDescription($description);
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::with('media')
            ->withCount(['products' => fn ($q) => $q->published()->visibleInCatalog()])
            ->where('status', CategoryStatus::ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}; ?>

<div class="page-fade">

    <div class="shell pt-4 pb-20">

        {{-- Breadcrumb --}}
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Categories</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-semibold tracking-tight">All Categories</h1>
            <p class="mt-1 text-[14px] text-ink-3">Browse our full range of commercial equipment.</p>
        </div>

        {{-- Grid --}}
        @if ($this->categories->isEmpty())
            <p class="py-20 text-center text-ink-3">No categories available yet.</p>
        @else
            <div class="grid grid-cols-2 gap-x-5 gap-y-7 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 2xl:grid-cols-7">
                @foreach ($this->categories as $category)
                    <a href="{{ route('category.show', $category) }}" wire:navigate class="group block transition">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-md bg-surface-sunken">
                            @if ($category->image_url)
                                @if ($placeholder = $category->image_placeholder)
                                    <img src="{{ $placeholder }}" alt="" aria-hidden="true"
                                        class="absolute inset-0 size-full scale-110 object-cover blur-xl" />
                                @endif
                                <picture class="contents">
                                    @if ($category->image_webp_url)
                                        <source srcset="{{ $category->image_webp_url }}" type="image/webp" />
                                    @endif
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy"
                                        x-data="{ loaded: false }" x-init="loaded = $el.complete" x-on:load="loaded = true"
                                        x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                        class="relative block size-full object-cover transition duration-500 group-hover:scale-[1.04]" />
                                </picture>
                            @else
                                <div class="flex size-full items-center justify-center bg-surface-sunken">
                                    <flux:icon.photo variant="outline" class="size-8 text-zinc-300" />
                                </div>
                            @endif
                        </div>
                        <div class="flex items-baseline justify-between gap-2 pt-2.5">
                            <div class="text-[11.5px] leading-tight font-semibold tracking-[0.06em] text-ink uppercase transition-colors group-hover:text-brand-500">
                                {{ $category->name }}
                            </div>
                            <div class="shrink-0 text-[11px] text-ink-3 tabular-nums">
                                {{ $category->products_count }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>

</div>
