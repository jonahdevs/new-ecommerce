<x-layouts::guest>
    <div class="container mx-auto px-4 py-4 min-h-[80svh]">

        {{-- Breadcrumb --}}
        @isset($breadcrumbs)
            <flux:breadcrumbs class="mb-4">
                <flux:breadcrumbs.item href="{{ route('home') }}">
                    <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                    Home
                </flux:breadcrumbs.item>
                @foreach ($breadcrumbs as $breadcrumb)
                    @if ($loop->last)
                        <flux:breadcrumbs.item>{{ $breadcrumb['title'] }}</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item href="{{ $breadcrumb['url'] }}">
                            {{ $breadcrumb['title'] }}
                        </flux:breadcrumbs.item>
                    @endif
                @endforeach
            </flux:breadcrumbs>
        @endisset

        <div class="flex flex-col lg:flex-row gap-4 mt-4">

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

                            <flux:navlist.item :href="route('customer.settings.profile')" wire:navigate icon="cog-8-tooth"
                                :current="request()->routeIs('customer.settings.*')">Settings</flux:navlist.item>

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
            <main class="flex-1 min-w-0 pb-8">
                {{ $slot }}
            </main>

        </div>
    </div>
</x-layouts::guest>
