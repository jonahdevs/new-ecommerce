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
                    @elseif ($category->icon_image_url)
                        <img src="{{ $category->icon_image_url }}" alt=""
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
        updateArrows() {
            const el = this.$refs.scroller;
            this.showLeft = el.scrollLeft > 10;
            this.showRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 10;
        },
        scrollByDir(dir) {
            this.$refs.scroller.scrollBy({ left: dir * 160, behavior: 'smooth' });
            setTimeout(() => this.updateArrows(), 300);
        },
    }" x-init="updateArrows()" @resize.window="updateArrows()"
        class="group relative shell flex items-center lg:hidden">

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
