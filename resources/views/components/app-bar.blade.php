<?php

use App\Services\{CompareService, WishlistService, CartService};
use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\{Computed, On};
use App\Enums\CategorySection;
use App\Services\QuoteBasketService;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public int $cartCount = 0;
    public int $compareCount = 0;
    public int $wishlistCount = 0;
    public int $quoteCount = 0;

    public function mount(WishlistService $wishlist, CompareService $compareService, CartService $cartService)
    {
        $this->cartCount = (int) session('cart_count', 0);
        $this->wishlistCount = (int) session('wishlist_count', 0);
        $this->compareCount = (int) session('compare_count', 0);
        $this->quoteCount = (int) session('quote_count', 0);
    }

    #[Computed]
    public function categories()
    {
        return Cache::tags(['navbar', 'categories'])->remember('navbar:categories', 60 * 60 * 12, function () {
            return Category::inSection(CategorySection::NAVBAR)->get();
        });
    }

    #[On('cart-updated')]
    public function refreshCartCount(CartService $cartService)
    {
        $this->cartCount = $cartService->getCount();
    }

    #[On('wishlist-updated')]
    public function updateWishlistCount(WishlistService $wishlistService): void
    {
        $this->wishlistCount = $wishlistService->getCount();
    }

    #[On('compare-updated')]
    public function updateCompareCount(CompareService $compareService): void
    {
        $this->compareCount = $compareService->getCount();
    }

    #[On('quote-basket-updated')]
    public function refreshQuoteCount(): void
    {
        $this->quoteCount = app(QuoteBasketService::class)->count();
    }
};
?>

<div>
    {{-- =====================================================================
         Main Header
         Mobile:  Logo | [search icon] | ♡ ⚖ 🛒 | Account
         Desktop: Logo | [Search bar ................] | ♡ ⚖ 🛒 | Account
         ===================================================================== --}}
    <nav class="w-full bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('images/stainless_steel.jpg') }}')">
        <section class="container mx-auto px-4 py-3 lg:py-4">
            <div class="flex items-center justify-between gap-3 sm:gap-4">

                {{-- Logo --}}
                <a href="{{ route('home') }}" wire:navigate class="flex items-center shrink-0">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('site.site.name') }} Logo"
                        class="h-8 sm:h-10 lg:h-12 w-auto transition-transform duration-300 hover:scale-105" />
                </a>

                {{-- Search — takes up available space on desktop, hidden on mobile --}}
                <div class="hidden lg:flex flex-1 min-w-0 px-6 xl:px-10">
                    @if (!($isErrorPage ?? false))
                        <livewire:search-bar />
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 sm:gap-3 lg:gap-5">

                    {{-- Mobile: search icon (rendered by search-bar component itself) --}}
                    {{-- We include the full component — it handles both mobile icon + desktop bar --}}
                    <div class="lg:hidden">
                        @if (!($isErrorPage ?? false))
                            <livewire:search-bar />
                        @endif
                    </div>

                    {{-- Wishlist — desktop only --}}
                    <a href="{{ route('wishlist') }}" wire:navigate class="hidden lg:flex items-center gap-2 group">
                        <div class="relative">
                            <flux:icon.heart
                                class="size-5 lg:size-6 text-zinc-800 group-hover:text-brand-primary transition-colors" />
                            @if ($wishlistCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-[10px] sm:text-xs rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center font-medium">
                                    {{ $wishlistCount }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs lg:text-sm font-medium text-zinc-800">Wishlist</span>
                    </a>

                    {{-- Compare — desktop only --}}
                    <a href="{{ route('products.compare') }}" wire:navigate
                        class="hidden lg:flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6 text-zinc-800 group-hover:text-brand-primary transition-colors"
                                viewBox="0 0 24 24" fill="none">
                                <g clip-path="url(#clip0_105_1836)">
                                    <path
                                        d="M13 3.99976H6C4.89543 3.99976 4 4.89519 4 5.99976V17.9998C4 19.1043 4.89543 19.9998 6 19.9998H13M17 3.99976H18C19.1046 3.99976 20 4.89519 20 5.99976V6.99976M20 16.9998V17.9998C20 19.1043 19.1046 19.9998 18 19.9998H17M20 10.9998V12.9998M12 1.99976V21.9998"
                                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_105_1836">
                                        <rect fill="white" height="24" transform="translate(0 -0.000244141)"
                                            width="24" />
                                    </clipPath>
                                </defs>
                            </svg>
                            @if ($compareCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-[10px] sm:text-xs font-medium rounded-full h-4 w-4 sm:h-5 sm:w-5 flex items-center justify-center">
                                    {{ $compareCount }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs lg:text-sm font-medium text-zinc-800">Compare</span>
                    </a>

                    {{-- Cart — always visible --}}
                    <a href="{{ route('cart') }}" wire:navigate class="flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-zinc-800 group-hover:text-brand-primary transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            @if ($cartCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-[10px] sm:text-xs font-medium rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </div>
                        <span class="hidden lg:inline text-xs lg:text-sm font-medium text-zinc-800">Cart</span>
                    </a>

                    {{-- Account Dropdown --}}
                    <flux:dropdown position="bottom" align="end" hover class="ms-2">
                        @auth
                            @auth
                                <button type="button" class="flex items-center gap-2 cursor-pointer">
                                    {{-- Avatar circle --}}
                                    @if (auth()->user()->avatar)
                                        <flux:avatar circle size="sm" src="{{ auth()->user()->avatar }}" />
                                    @else
                                        <flux:avatar circle size="sm" name="{{ auth()->user()->name }}" />
                                    @endif

                                    {{-- Name — md+ only --}}
                                    <span class="hidden md:block text-xs lg:text-sm font-medium text-zinc-800">
                                        {{ auth()->user()->name }}
                                    </span>

                                    {{-- Chevron — md+ only --}}
                                    <svg class="hidden md:block w-4 h-4 text-zinc-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @else
                                ...
                            @endauth
                        @else
                            <button type="button"
                                class="flex items-center gap-2 hover:text-brand-secondary transition-colors">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-zinc-800" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <div class="hidden lg:block">
                                    <div class="text-xs lg:text-sm font-medium text-zinc-800">Account</div>
                                </div>
                                <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4 hidden lg:block text-zinc-600" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endauth

                        <flux:navmenu @class([
                            'rounded-sm! shadow-2xl!',
                            'mt-[9px]! md:mt-4.5! ' => auth()->check(),
                            'mt-5.5!' => !auth()->check(),
                        ])>
                            <flux:navmenu.item :href="route('customer.account')" wire:navigate icon="user"
                                icon-variant="outline">
                                Account
                            </flux:navmenu.item>
                            <flux:navmenu.item :href="route('customer.orders.index')" wire:navigate icon="package"
                                icon-variant="outline">
                                Orders
                            </flux:navmenu.item>
                            <flux:navmenu.item :href="route('quote')" wire:navigate icon="document-text"
                                icon-variant="outline">
                                <span class="flex items-center gap-2 w-full">
                                    Quote Basket
                                    @if ($quoteCount > 0)
                                        <span
                                            class="ms-auto bg-amber-500 text-white text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ $quoteCount }}
                                        </span>
                                    @endif
                                </span>
                            </flux:navmenu.item>
                            <flux:navmenu.item :href="route('wishlist')" wire:navigate icon="heart"
                                icon-variant="outline">
                                <span class="flex items-center gap-2  w-full">
                                    Wishlist
                                    @if ($wishlistCount > 0)
                                        <span
                                            class="ms-auto bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ $wishlistCount }}
                                        </span>
                                    @endif
                                </span>
                            </flux:navmenu.item>
                            <flux:navmenu.item :href="route('products.compare')" wire:navigate icon="arrows-right-left"
                                icon-variant="outline">
                                <span class="flex items-center gap-2  w-full">
                                    Compare
                                    @if ($compareCount > 0)
                                        <span
                                            class="ms-auto bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ $compareCount }}
                                        </span>
                                    @endif
                                </span>
                            </flux:navmenu.item>
                            <flux:menu.separator />
                            @auth
                                <form action="{{ route('logout') }}" method="post">
                                    @csrf
                                    <flux:navmenu.item type="submit" icon="arrow-right-start-on-rectangle" variant="danger"
                                        class="cursor-pointer">
                                        Logout
                                    </flux:navmenu.item>
                                </form>
                            @else
                                <flux:navmenu.item href="{{ route('login') }}" wire:navigate
                                    icon="arrow-left-start-on-rectangle" class="cursor-pointer">
                                    Log in
                                </flux:navmenu.item>
                            @endauth
                        </flux:navmenu>
                    </flux:dropdown>

                </div>
            </div>
        </section>
    </nav>

    {{-- =====================================================================
         Category Navigation
         Desktop: 6-column grid
         Mobile:  horizontal scroll with fade chevrons
         ===================================================================== --}}
    <nav class="bg-brand-primary text-white">

        {{-- Desktop --}}
        <section class="container mx-auto px-4 hidden lg:block">
            <ul class="m-0 flex flex-wrap border-r border-white/20 p-0" data-language="en" role="menubar"
                aria-label="Main navigation menu">
                @foreach ($this->categories->take(12) as $category)
                    <li class="w-[16.66666666666667%] cursor-pointer hover:bg-brand-primary-dark" tabindex="0"
                        role="menuitem" aria-expanded="false">
                        <div class="relative h-9.25">
                            <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                                class="flex min-h-full items-center overflow-hidden text-ellipsis whitespace-nowrap border-l border-white/20 px-1.25 xl:px-2.5 border-b">
                                <img alt="" loading="eager" width="26" height="26" decoding="async"
                                    class="duration-300 max-h-6.5 max-w-6.5 max-md:hidden invert"
                                    style="color:transparent" src="{{ $category->icon_url }}">
                                <span
                                    class="ml-2 truncate text-xs lg:text-sm text-zinc-50">{{ $category->name }}</span>
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
                        :class="browseOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- Dropdown panel --}}
                <div x-cloak x-show="browseOpen" x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute left-0 top-full z-50 w-72 bg-white rounded-b-lg shadow-xl border border-t-0 border-zinc-200 overflow-hidden"
                    @click="browseOpen = false">

                    <ul class="divide-y divide-zinc-100 max-h-[60vh] overflow-y-auto" role="menu"
                        aria-label="Browse categories">
                        @foreach ($this->categories as $category)
                            <li>
                                <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                                    class="flex items-center gap-3 px-4 py-2.5 text-xs sm:text-sm text-zinc-800 hover:bg-zinc-50 transition-colors">
                                    <img src="{{ $category->icon_url }}" alt="" width="20"
                                        height="20" class="max-w-5 max-h-5 opacity-60">
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
                class="invisible group-hover:visible absolute left-0 z-10 flex items-center justify-center w-8 h-full bg-linear-to-r from-brand-primary via-brand-primary/90 to-transparent text-white shrink-0 cursor-pointer"
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
                class="invisible group-hover:visible absolute right-0 z-10 flex items-center justify-center w-8 h-full bg-linear-to-l from-brand-primary via-brand-primary/90 to-transparent text-white shrink-0 cursor-pointer"
                aria-label="Scroll right">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </section>
    </nav>
</div>
