{{-- $navCategories is computed once in layouts/storefront.blade.php.

     Two responsive layouts (pattern carried over from the previous site):
       • lg+   : capped 2-row grid (6 cols).
       • < lg  : a single bar with a "Browse" dropdown (full, scrollable list — deep access)
                 plus a horizontal scroll strip with fade/chevron affordances (broad swipe browse). --}}
<nav class="bg-brand-blue-500 text-[#f2ead9]">

    {{-- Desktop (lg+) — grid-rows-2 + auto-rows-[0] caps visible content at exactly 2 rows.
         Items in implicit (overflow) rows collapse to 0 height and get clipped.
         gap-px + a subtle divider color on the parent paints 1px dividers between cells. --}}
    <div class="shell hidden lg:block">
        <div
            class="grid grid-cols-6 grid-rows-2 auto-rows-[0] gap-px overflow-hidden border-x border-white/20 bg-white/20">
            @foreach ($navCategories as $category)
                @php
                    $isActive =
                        request()->routeIs('category.show') && request()->route('category')?->id === $category->id;
                @endphp
                <a href="{{ route('category.show', $category) }}" wire:navigate @class([
                    'flex items-center gap-2 px-3 py-2.5 text-sm transition',
                    'bg-brand-blue-500 text-[#f2ead9] hover:bg-brand-blue-600 hover:text-white' => !$isActive,
                    'bg-brand-blue-700 font-medium text-white' => $isActive,
                ])>
                    @if ($category->icon_svg)
                        <span class="grid size-5 shrink-0 place-items-center [&>svg]:size-full">
                            {!! $category->icon_svg !!}
                        </span>
                    @elseif ($category->icon)
                        <img src="{{ $category->icon_url }}" alt=""
                            class="size-5 shrink-0 object-contain brightness-0 invert" loading="lazy" />
                    @endif
                    <span class="truncate">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Mobile / tablet (< lg) — Browse dropdown + horizontal scroller with edge fades --}}
    <section x-data="{
        showLeft: false,
        showRight: true,
        browseOpen: false,
        updateArrows() {
            const el = this.$refs.scroller;
            this.showLeft = el.scrollLeft > 10;
            this.showRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 10;
        },
        scrollByDir(dir) {
            this.$refs.scroller.scrollBy({ left: dir * 160, behavior: 'smooth' });
            setTimeout(() => this.updateArrows(), 300);
        },
    }" x-init="updateArrows()" @resize.window="updateArrows()" @click.outside="browseOpen = false"
        class="group relative shell flex items-center lg:hidden">

        {{-- Browse Categories button --}}
        <div class="relative shrink-0">
            <button type="button" @click="browseOpen = !browseOpen" :aria-expanded="browseOpen" aria-haspopup="true"
                class="mr-1 flex items-center gap-1.5 border-r border-white/20 py-3 pr-3 text-xs font-medium whitespace-nowrap text-white sm:text-sm">
                <flux:icon.bars-3 variant="outline" class="size-4 shrink-0" />
                Browse
                <flux:icon.chevron-down variant="micro" class="size-3 shrink-0 transition-transform duration-200"
                    x-bind:class="browseOpen && 'rotate-180'" />
            </button>

            {{-- Dropdown panel — full, scrollable category list --}}
            <div x-cloak x-show="browseOpen" @click="browseOpen = false"
                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                class="absolute top-full left-0 z-50 w-72 overflow-hidden rounded-b-md border border-t-0 border-zinc-200 bg-white shadow-xl">
                <ul class="scrollbar-thin max-h-[60vh] divide-y divide-zinc-100 overflow-y-auto" role="menu"
                    aria-label="Browse categories">
                    @foreach ($navCategories as $category)
                        <li>
                            <a href="{{ route('category.show', $category) }}" wire:navigate
                                class="flex items-center gap-3 px-4 py-2.5 text-[13.5px] text-ink transition hover:bg-surface-sunken">
                                @if ($category->icon_svg)
                                    <span class="grid size-5 shrink-0 place-items-center text-ink-3 [&>svg]:size-full">
                                        {!! $category->icon_svg !!}
                                    </span>
                                @elseif ($category->icon)
                                    <img src="{{ $category->icon_url }}" alt=""
                                        class="size-5 shrink-0 object-contain opacity-60" loading="lazy" />
                                @endif
                                <span class="truncate">{{ $category->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Left chevron (appears once scrolled) --}}
        <button type="button" x-cloak x-show="showLeft" x-transition.opacity @click="scrollByDir(-1)"
            aria-label="Scroll categories left"
            class="invisible absolute left-0 z-10 flex h-full w-8 cursor-pointer items-center justify-center bg-linear-to-r from-brand-blue-500 via-brand-blue-500/90 to-transparent text-white group-hover:visible">
            <flux:icon.chevron-left variant="micro" class="size-4" />
        </button>

        {{-- Scrollable strip --}}
        <div x-ref="scroller" @scroll="updateArrows()"
            class="flex w-full overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($navCategories as $category)
                @php
                    $isActive =
                        request()->routeIs('category.show') && request()->route('category')?->id === $category->id;
                @endphp
                <a href="{{ route('category.show', $category) }}" wire:navigate @class([
                    'shrink-0 px-3 py-3 text-xs whitespace-nowrap transition sm:text-sm sm:px-4',
                    'font-medium text-white' => $isActive,
                    'hover:opacity-80' => !$isActive,
                ])>
                    {{ $category->name }}
                </a>
            @endforeach
        </div>

        {{-- Right chevron --}}
        <button type="button" x-cloak x-show="showRight" x-transition.opacity @click="scrollByDir(1)"
            aria-label="Scroll categories right"
            class="invisible absolute right-0 z-10 flex h-full w-8 cursor-pointer items-center justify-center bg-linear-to-l from-brand-blue-500 via-brand-blue-500/90 to-transparent text-white group-hover:visible">
            <flux:icon.chevron-right variant="micro" class="size-4" />
        </button>
    </section>
</nav>
