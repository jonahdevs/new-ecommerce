<?php

use App\Services\{CompareService, WishlistService, CartService};
use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\{Computed, On};
use App\Enums\CategorySection;
use App\Services\QuoteBasketService;

new class extends Component {
    public int $cartCount = 0;
    public int $compareCount = 0;
    public int $wishlistCount = 0;
    public int $quoteCount = 0;

    public function mount(WishlistService $wishlist, CompareService $compareService, CartService $cartService)
    {
        $this->cartCount = $cartService->getCount();
        $this->wishlistCount = $wishlist->getCount();
        $this->compareCount = $compareService->getCount();
        $this->quoteCount = app(QuoteBasketService::class)->count();
    }

    #[Computed(persist: true)]
    public function categories()
    {
        return Category::inSection(CategorySection::NAVBAR)->get();
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
                                class="size-6 text-zinc-800 group-hover:text-brand-primary transition-colors" />
                            @if ($wishlistCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-xs rounded-full w-5 h-5 flex items-center justify-center font-medium">
                                    {{ $wishlistCount }}
                                </span>
                            @endif
                        </div>
                        <span class="text-sm font-medium text-zinc-800">Wishlist</span>
                    </a>

                    {{-- Compare — desktop only --}}
                    <a href="{{ route('products.compare') }}" wire:navigate
                        class="hidden lg:flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-6 h-6 text-zinc-800 group-hover:text-brand-primary transition-colors"
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
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                    {{ $compareCount }}
                                </span>
                            @endif
                        </div>
                        <span class="text-sm font-medium text-zinc-800">Compare</span>
                    </a>

                    {{-- Cart — always visible --}}
                    <a href="{{ route('cart') }}" wire:navigate class="flex items-center gap-2 group">
                        <div class="relative">
                            <svg class="w-6 h-6 text-zinc-800 group-hover:text-brand-primary transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            @if ($cartCount > 0)
                                <span
                                    class="absolute -top-2 -right-2 bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full w-5 h-5 flex items-center justify-center">
                                    {{ $cartCount }}
                                </span>
                            @endif
                        </div>
                        <span class="hidden lg:inline text-sm font-medium text-zinc-800">Cart</span>
                    </a>

                    {{-- Account Dropdown --}}
                    <flux:dropdown position="bottom" align="end" hover>
                        @auth
                            @if (auth()->user()->avatar)
                                <flux:profile circle avatar="{{ auth()->user()->avatar }}" :chevron="false"
                                    class="md:hidden" />
                            @else
                                <flux:profile circle avatar:name="{{ auth()->user()->name }}" :chevron="false"
                                    class="md:hidden" />
                            @endif

                            @if (auth()->user()->avatar)
                                <flux:profile circle avatar="{{ auth()->user()->avatar }}"
                                    name="{{ auth()->user()->name }}" class="hidden md:flex" />
                            @else
                                <flux:profile circle name="{{ auth()->user()->name }}" class="hidden md:flex" />
                            @endif
                        @else
                            <button type="button"
                                class="flex items-center gap-2 hover:text-brand-secondary transition-colors">
                                <svg class="w-6 h-6 text-zinc-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <div class="hidden lg:block">
                                    <div class="text-sm font-medium text-zinc-800">Account</div>
                                </div>
                                <svg class="w-4 h-4 hidden lg:block text-zinc-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endauth

                        <flux:navmenu @class([
                            'rounded-sm!',
                            'mt-4!' => auth()->check(),
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

                            @auth
                                <flux:navmenu.item :href="route('customer.quotations.index')" wire:navigate
                                    icon="clipboard-document-list" icon-variant="outline">
                                    My Quotations
                                </flux:navmenu.item>
                            @endauth
                            <flux:navmenu.item :href="route('quote')" wire:navigate icon="document-text"
                                icon-variant="outline">
                                <span class="flex items-center gap-2">
                                    Quote Basket
                                    @if ($quoteCount > 0)
                                        <span
                                            class="bg-amber-500 text-white text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ $quoteCount }}
                                        </span>
                                    @endif
                                </span>
                            </flux:navmenu.item>
                            <flux:navmenu.item :href="route('wishlist')" wire:navigate icon="heart"
                                icon-variant="outline">
                                Wishlist
                            </flux:navmenu.item>
                            <flux:navmenu.item :href="route('products.compare')" wire:navigate icon="arrows-right-left"
                                icon-variant="outline">
                                <span class="flex items-center gap-2">
                                    Compare
                                    @if ($compareCount > 0)
                                        <span
                                            class="bg-brand-primary text-brand-primary-content text-xs font-medium rounded-full h-5 w-5 flex items-center justify-center">
                                            {{ $compareCount }}
                                        </span>
                                    @endif
                                </span>
                            </flux:navmenu.item>
                            @auth
                                <flux:navmenu.item href="#" wire:navigate icon="envelope" icon-variant="outline">
                                    Messages
                                </flux:navmenu.item>
                            @endauth
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
                                <span class="ml-2 truncate text-sm text-zinc-50">{{ $category->name }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Mobile: horizontal scroll --}}
        <section x-data="{
            showLeft: false,
            showRight: true,
            updateArrows() {
                const el = this.$refs.scroller;
                this.showLeft = el.scrollLeft > 10;
                this.showRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 10;
            },
            scrollLeft() { this.$refs.scroller.scrollBy({ left: -160, behavior: 'smooth' }); },
            scrollRight() { this.$refs.scroller.scrollBy({ left: 160, behavior: 'smooth' }); }
        }" x-init="updateArrows()" @mouseover="$el.classList.add('hovered')"
            @mouseleave="$el.classList.remove('hovered')"
            class="group relative container mx-auto px-4 lg:hidden flex items-center">

            {{-- Left chevron --}}
            <button x-show="showLeft" x-transition:enter="transition ease-out duration-200"
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
                        class="shrink-0 px-4 py-3 text-sm hover:opacity-80 transition-opacity duration-500 whitespace-nowrap">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>

            {{-- Right chevron --}}
            <button x-show="showRight" x-transition:enter="transition ease-out duration-200"
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
