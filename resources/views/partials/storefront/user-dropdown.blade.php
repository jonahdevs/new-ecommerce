<flux:dropdown position="bottom" align="end" gap="10">
    @auth
        <button type="button" aria-label="Account"
            class="inline-flex size-10 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken">
            <span class="flex size-7 items-center justify-center rounded-full bg-brand-500 text-[11px] font-bold text-white">
                {{ auth()->user()->initials() }}
            </span>
        </button>
    @else
        <button type="button" aria-label="Sign in"
            class="inline-flex size-10 items-center justify-center rounded-md text-ink-2 transition hover:bg-surface-sunken hover:text-ink">
            <flux:icon.user variant="micro" class="size-5" />
        </button>
    @endauth

    <div popover="manual" class="w-75 max-w-[calc(100vw-1rem)] rounded-md border border-zinc-200 bg-white shadow-lg focus:outline-hidden">
        @auth
            <div class="flex items-center gap-3 border-b border-zinc-200 px-4 py-4">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">
                    {{ auth()->user()->initials() }}
                </span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">{{ auth()->user()->name }}</div>
                    <div class="truncate text-[12px] text-ink-3">{{ auth()->user()->email }}</div>
                </div>
            </div>

            @php $isStaff = auth()->user()->roles->isNotEmpty(); @endphp

            @if ($isStaff)
                {{-- Staff don't have a customer self-service area — link them into
                     the admin panel instead of the customer account pages. --}}
                <div class="py-1.5">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.squares-2x2 variant="micro" class="size-4 text-ink-3" />
                        Admin dashboard
                    </a>
                    <a href="{{ route('admin.settings.general') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.cog-6-tooth variant="micro" class="size-4 text-ink-3" />
                        Settings
                    </a>
                </div>

                <div class="border-t border-zinc-200 py-1.5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 px-4 py-2 text-left text-[13.5px] text-brand-500 hover:bg-surface-sunken">
                            <flux:icon.x variant="micro" class="size-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            @else
                <div class="py-1.5">
                    <a href="{{ route('account.dashboard') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.user variant="micro" class="size-4 text-ink-3" />
                        Account dashboard
                    </a>
                    <a href="{{ route('account.orders.index') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.document-text variant="micro" class="size-4 text-ink-3" />
                        Orders
                    </a>
                    <a href="{{ route('wishlist') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.heart variant="micro" class="size-4 text-ink-3" />
                        Wishlist
                    </a>
                    <a href="{{ route('account.quotes.index') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.scale variant="micro" class="size-4 text-ink-3" />
                        My quotes
                    </a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.shield-check variant="micro" class="size-4 text-ink-3" />
                        Service contracts
                    </a>
                </div>

                <div class="border-t border-zinc-200 py-1.5">
                    <a href="{{ route('profile.edit') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.cog-6-tooth variant="micro" class="size-4 text-ink-3" />
                        Account settings
                    </a>
                    <a href="{{ route('contact') }}" wire:navigate
                        class="flex items-center gap-3 px-4 py-2 text-[13.5px] text-ink hover:bg-surface-sunken">
                        <flux:icon.chat-bubble-left-right variant="micro" class="size-4 text-ink-3" />
                        Contact specialist
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 px-4 py-2 text-left text-[13.5px] text-brand-500 hover:bg-surface-sunken">
                            <flux:icon.x variant="micro" class="size-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            @endif
        @else
            <div class="px-4 pt-4 pb-3">
                <div class="font-serif text-lg text-ink">Welcome to Sheffield</div>
                <p class="mt-1 text-[12.5px] text-ink-3">
                    Sign in to track orders, save quotes and manage your service contracts.
                </p>
                <div class="mt-4 flex flex-col gap-2">
                    <flux:button variant="customer-primary" size="customer" :href="route('login')" wire:navigate
                        class="w-full!">Sign in</flux:button>
                    <flux:button variant="customer-outline" size="customer" :href="route('register')" wire:navigate
                        class="w-full!">Create an account</flux:button>
                </div>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3.5">
                <div class="mb-2 text-[11px] font-bold tracking-[0.08em] text-ink-3 uppercase">For businesses</div>
                <a href="{{ route('register') }}" wire:navigate
                    class="flex items-center gap-2.5 text-[13px] text-ink hover:text-brand-500">
                    <flux:icon.shield-check variant="micro" class="size-4 text-brand-500" />
                    Apply for trade account &amp; Net 30 <flux:icon.arrow-right variant="micro" class="size-3.5" />
                </a>
            </div>
        @endauth
    </div>
</flux:dropdown>
