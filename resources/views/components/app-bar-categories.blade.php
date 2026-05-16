<?php

use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\Computed;
use App\Enums\CategorySection;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    #[Computed]
    public function categories()
    {
        return Cache::tags(['navbar', 'categories'])->remember('navbar:categories', 60 * 60 * 12, function () {
            return Category::inSection(CategorySection::NAVBAR)->get(['id', 'name', 'slug', 'image_icon']);
        });
    }
};
?>

<nav class="bg-primary text-white">

    {{-- Desktop --}}
    <section class="container mx-auto px-4 hidden lg:block">
        <ul class="m-0 flex flex-wrap border-r border-white/20 p-0" data-language="en" role="menubar"
            aria-label="Main navigation menu">
            @foreach ($this->categories->take(12) as $category)
                <li class="w-[16.66666666666667%] cursor-pointer hover:bg-primary-hover" tabindex="0" role="menuitem"
                    aria-expanded="false">
                    <div class="relative h-9.25">
                        <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                            class="flex min-h-full items-center overflow-hidden text-ellipsis whitespace-nowrap border-l border-white/20 px-1.25 xl:px-2.5 border-b">
                            <img alt="" loading="eager" width="26" height="26" decoding="async"
                                class="duration-300 max-h-6.5 max-w-6.5 max-md:hidden invert" style="color:transparent"
                                src="{{ $category->icon_url }}">
                            <span class="ml-2 truncate text-xs lg:text-sm text-zinc-50">{{ $category->name }}</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Mobile: horizontal scroll + Browse dropdown --}}
    <section x-data="{
        showLeft: false,
        showRight: true,
        browseOpen: false,
        updateArrows() {
            const el = this.$refs.scroller;
            this.showLeft = el.scrollLeft > 10;
            this.showRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 10;
        },
        scrollLeft() { this.$refs.scroller.scrollBy({ left: -160, behavior: 'smooth' }); },
        scrollRight() { this.$refs.scroller.scrollBy({ left: 160, behavior: 'smooth' }); }
    }" x-init="updateArrows()" @mouseover="$el.classList.add('hovered')"
        @mouseleave="$el.classList.remove('hovered')" @click.outside="browseOpen = false"
        class="group relative container mx-auto px-4 lg:hidden flex items-center">

        {{-- Browse Categories button --}}
        <div class="relative shrink-0">
            <button @click="browseOpen = !browseOpen" :aria-expanded="browseOpen"
                class="flex items-center gap-1.5 py-3 pr-3 text-xs sm:text-sm font-medium text-white whitespace-nowrap border-r border-white/20 mr-1"
                aria-haspopup="true">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Browse
                <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 shrink-0 transition-transform duration-200"
                    :class="browseOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Dropdown panel --}}
            <div x-cloak x-show="browseOpen" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                class="absolute left-0 top-full z-50 w-72 bg-white rounded-b-lg shadow-xl border border-t-0 border-zinc-200 overflow-hidden"
                @click="browseOpen = false">

                <ul class="divide-y divide-zinc-100 max-h-[60vh] overflow-y-auto" role="menu"
                    aria-label="Browse categories">
                    @foreach ($this->categories as $category)
                        <li>
                            <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                                class="flex items-center gap-3 px-4 py-2.5 text-xs sm:text-sm text-zinc-800 hover:bg-zinc-50 transition-colors">
                                <img src="{{ $category->icon_url }}" alt="" width="20" height="20"
                                    class="max-w-5 max-h-5 opacity-60">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Left chevron --}}
        <button x-cloak x-show="showLeft" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="scrollLeft(); setTimeout(() => updateArrows(), 300)"
            class="invisible group-hover:visible absolute left-0 z-10 flex items-center justify-center w-8 h-full bg-linear-to-r from-primary via-primary/90 to-transparent text-white shrink-0 cursor-pointer"
            aria-label="Scroll left">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        {{-- Scrollable list --}}
        <div x-ref="scroller" @scroll="updateArrows()"
            class="flex overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] w-full">
            @foreach ($this->categories as $category)
                <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                    class="shrink-0 px-3 sm:px-4 py-3 text-xs sm:text-sm hover:opacity-80 transition-opacity duration-500 whitespace-nowrap">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        {{-- Right chevron --}}
        <button x-cloak x-show="showRight" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="scrollRight(); setTimeout(() => updateArrows(), 300)"
            class="invisible group-hover:visible absolute right-0 z-10 flex items-center justify-center w-8 h-full bg-linear-to-l from-primary via-primary/90 to-transparent text-white shrink-0 cursor-pointer"
            aria-label="Scroll right">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </section>
</nav>
