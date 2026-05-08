<x-layouts::guest>
    {{-- BREADCRUMB --}}
    <div class="bg-zinc-100 py-2.5">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item current>{{ $title ?? 'My Account' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-5 lg:py-7 pb-12 lg:pb-15">

        <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] lg:grid-cols-[260px_1fr] gap-4 lg:gap-6 items-start">

            {{-- ===== SIDEBAR ===== --}}
            <aside x-data="{
                open: false,
                init() {
                    this.open = window.innerWidth >= 1024;
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) {
                            this.open = true;
                        }
                    });
                }
            }" class="w-full lg:w-64 shrink-0 lg:self-start lg:sticky lg:top-28">
                <div class="bg-white rounded-lg border px-4 py-4">

                    {{-- Profile card --}}
                    <div class="flex items-center gap-3">
                        @if (auth()->user()->avatar)
                            <flux:avatar circle class="size-12 shrink-0" src="{{ auth()->user()->avatar }}" />
                        @else
                            <flux:avatar circle class="size-12 shrink-0" name="{{ auth()->user()->name }}" />
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-zinc-800 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-zinc-500 text-xs truncate">{{ auth()->user()->email }}</p>
                        </div>

                        {{-- Toggle button — mobile only --}}
                        <button @click="open = !open"
                            class="lg:hidden p-2 rounded-md text-zinc-500 hover:bg-zinc-100 transition-colors">
                            <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Nav links --}}
                    <div x-show="open" x-collapse class="mt-4 border-t pt-4">
                        <flux:navlist class="w-full [&_svg]:w-5 [&_svg]:h-5">

                            <flux:navlist.item :href="route('customer.account')" icon="user" wire:navigate
                                :current="request()->routeIs('customer.account')">My Account</flux:navlist.item>

                            <flux:navlist.item :href="route('customer.orders.index')" icon="package" wire:navigate
                                :current="request()->routeIs('customer.orders.*')"
                                :badge="\App\Models\Order::where('user_id', auth()->id())->count() ?: null">
                                Orders
                            </flux:navlist.item>

                            <flux:navlist.item :href="route('customer.quotations.index')" icon="tag" wire:navigate
                                :current="request()->routeIs('customer.quotations.*')"
                                :badge="\App\Models\Quote::where('user_id', auth()->id())
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ->where('status', \App\Enums\QuoteStatus::SENT)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ->count() ?: null">
                                Quotations
                            </flux:navlist.item>

                            <flux:navlist.item :href="route('customer.inbox')" wire:navigate icon="envelope"
                                :current="request()->routeIs('customer.inbox')"
                                :badge="auth()->user()->unreadNotifications()->count() ?: null">
                                Inbox
                            </flux:navlist.item>

                            <flux:navlist.item :href="route('customer.pending-reviews')" wire:navigate icon="star"
                                :current="request()->routeIs('customer.pending-reviews')">
                                Pending Reviews
                            </flux:navlist.item>

                            <flux:navlist.item :href="route('customer.recently-viewed')" wire:navigate icon="eye"
                                :current="request()->routeIs('customer.recently-viewed')">
                                Recently Viewed
                            </flux:navlist.item>

                            <flux:navlist.item :href="route('wishlist')" wire:navigate icon="heart"
                                :badge="auth()->user()->wishlistItems()->count() ?: null">Favorite Items
                            </flux:navlist.item>

                            <flux:separator class="my-2" />

                            <flux:navlist.item :href="route('customer.settings.profile')" wire:navigate
                                icon="cog-8-tooth" :current="request()->routeIs('customer.settings.*')">Settings
                            </flux:navlist.item>

                            <flux:navlist.item :href="route('customer.address-book.index')" wire:navigate
                                icon="book-open">Address Book</flux:navlist.item>

                            <flux:separator class="my-2" />

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <flux:navlist.item type="submit" icon="arrow-right-start-on-rectangle"
                                    class="text-red-500 hover:bg-red-50 cursor-pointer">Logout</flux:navlist.item>
                            </form>

                        </flux:navlist>
                    </div>

                </div>
            </aside>

            {{-- ===== MAIN CONTENT ===== --}}
            <div class="flex flex-col gap-5">
                {{-- STATS BAR - Show on all customer pages --}}
                @php
                    $userId = auth()->id();
                    $stats = [
                        [
                            'label' => 'Total Orders',
                            'value' => \App\Models\Order::where('user_id', $userId)->count(),
                            'iconBg' => 'bg-[#fff4f0]',
                            'iconColor' => 'text-primary',
                            'icon' =>
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>',
                        ],
                        [
                            'label' => 'Wishlist Items',
                            'value' => auth()->user()->wishlistItems()->count(),
                            'iconBg' => 'bg-[#fff0f0]',
                            'iconColor' => 'text-red-500',
                            'icon' =>
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                        ],
                        [
                            'label' => 'Reviews Left',
                            'value' => auth()->user()->reviews()->count(),
                            'iconBg' => 'bg-[#fffbf0]',
                            'iconColor' => 'text-amber-500',
                            'icon' =>
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                        ],
                        [
                            'label' => 'Returns',
                            'value' => \App\Models\Order::where('user_id', $userId)
                                ->where('status', \App\Enums\OrderStatus::RETURNED ?? 'returned')
                                ->count(),
                            'iconBg' => 'bg-[#f0f8ff]',
                            'iconColor' => 'text-blue-500',
                            'icon' =>
                                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg>',
                        ],
                    ];
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 bg-white border-[1.5px] border-zinc-200">
                    @foreach ($stats as $stat)
                        <div
                            class="flex items-center gap-3.5 p-5 border-zinc-200 max-md:odd:border-r max-md:nth-3:border-t max-md:nth-4:border-t md:border-r md:last:border-r-0">
                            <div
                                class="flex items-center justify-center w-10.5 h-10.5 shrink-0 {{ $stat['iconBg'] }} [&_svg]:w-5 [&_svg]:h-5 {{ $stat['iconColor'] }}">
                                {!! $stat['icon'] !!}
                            </div>
                            <div>
                                <div class="text-[11px] text-zinc-500 font-medium tracking-wide mb-0.5">
                                    {{ $stat['label'] }}</div>
                                <div class="font-barlow-condensed text-[28px] font-black text-zinc-950 leading-none">
                                    {{ $stat['value'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <main class="page-transition">
                    {{ $slot }}
                </main>
            </div>

        </div>
    </div>
</x-layouts::guest>
