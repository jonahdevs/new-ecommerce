<?php

use App\Enums\CategorySection;
use App\Enums\CategoryStatus;
use App\Models\CategoryPlacement;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::storefront')] #[Title('Home')] class extends Component
{
    // TODO: cache this once it becomes hot.
    #[Computed]
    public function featuredCategories(): Collection
    {
        return CategoryPlacement::query()
            ->with('category')
            ->where('location', CategorySection::HOME_PAGE_FEATURED)
            ->where('status', CategoryStatus::ACTIVE)
            ->orderBy('sort_order')
            ->get()
            ->pluck('category')
            ->filter();
    }
}; ?>

<div>
    {{-- Hero --}}
    <section class="bg-zinc-50 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:py-24">
            <div class="max-w-2xl">
                <flux:heading size="xl" level="1">
                    Commercial kitchen equipment, built to last.
                </flux:heading>
                <flux:text class="mt-4 text-base">
                    Restaurants, hotels, and catering operations across East Africa trust us
                    for ovens, refrigeration, displays, and prep equipment from the world's leading brands.
                </flux:text>
                <div class="mt-6 flex flex-wrap gap-3">
                    <flux:button variant="primary" href="#" wire:navigate>Shop the catalog</flux:button>
                    <flux:button variant="ghost" href="#" wire:navigate>Talk to a specialist</flux:button>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured categories --}}
    <section class="mx-auto max-w-7xl px-6 py-16">
        <div class="mb-8 flex items-end justify-between">
            <flux:heading size="lg" level="2">Shop by category</flux:heading>
            <a href="#" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100" wire:navigate>
                View all &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($this->featuredCategories as $category)
                <a
                    href="#"
                    wire:navigate
                    class="group flex flex-col items-center rounded-lg border border-zinc-200 bg-white p-4 text-center transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
                >
                    @if ($category->icon)
                        <img
                            src="{{ asset('storage/'.$category->icon) }}"
                            alt="{{ $category->name }}"
                            class="size-14 object-contain"
                            loading="lazy"
                        />
                    @else
                        <flux:icon.squares-2x2 class="size-14 text-zinc-400" />
                    @endif
                    <span class="mt-3 text-sm font-medium text-zinc-900 group-hover:text-zinc-700 dark:text-zinc-100 dark:group-hover:text-zinc-300">
                        {{ $category->name }}
                    </span>
                </a>
            @endforeach
        </div>
    </section>
</div>
