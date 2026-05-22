@php
    $user = auth()->user();
    $ordersCount = \App\Models\Order::where('user_id', $user->id)->count();
    $quotesCount = \App\Models\Quote::where('user_id', auth()->id())
        ->where('status', \App\Enums\QuoteStatus::SENT)
        ->count();
    $wishlistCount = $user->wishlistItems()->count();
@endphp

<aside class="customer-sidebar w-full lg:w-64 shrink-0 lg:self-start lg:sticky lg:top-36">
    <div class="bg-white rounded-md border  py-4">

        {{-- Profile card --}}
        <div class="flex items-center gap-3 px-4">
            @if ($user->avatar)
                <flux:avatar circle class="size-12 shrink-0" src="{{ $user->avatar }}" />
            @else
                <flux:avatar circle class="size-12 shrink-0" name="{{ $user->name }}" />
            @endif

            <div class="flex-1 min-w-0">
                <p class="font-semibold text-on-surface truncate">{{ $user->name }}</p>
                <p class="text-on-surface-variant text-xs truncate">{{ $user->email }}</p>
            </div>
        </div>

        {{-- Nav links --}}
        <div class="mt-4 border-t pt-4">
            <flux:navlist class="w-full [&_svg]:w-5 [&_svg]:h-5">

                <flux:navlist.item :href="route('customer.account')" icon="user" wire:navigate
                    :current="request()->routeIs('customer.account')">
                    My Account
                </flux:navlist.item>

                <flux:navlist.item :href="route('customer.orders.index')" icon="package" wire:navigate
                    :current="request()->routeIs('customer.orders.*')" :badge="$ordersCount">
                    Orders
                </flux:navlist.item>

                <flux:navlist.item :href="route('customer.address-book.index')" wire:navigate icon="book-open">
                    Address Book
                </flux:navlist.item>

                <flux:separator class="my-2" />

                <flux:navlist.item :href="route('wishlist')" wire:navigate icon="heart" :badge="$wishlistCount">
                    Wishlist
                </flux:navlist.item>

                <flux:navlist.item :href="route('customer.recently-viewed')" wire:navigate icon="eye"
                    :current="request()->routeIs('customer.recently-viewed')">
                    Recently Viewed
                </flux:navlist.item>

                <flux:navlist.item :href="route('customer.quotations.index')" icon="tag" wire:navigate
                    :current="request()->routeIs('customer.quotations.*')" :badge="$quotesCount">
                    Quotations
                </flux:navlist.item>

                <flux:navlist.item :href="route('customer.pending-reviews')" wire:navigate icon="star"
                    :current="request()->routeIs('customer.pending-reviews')">
                    Pending Reviews
                </flux:navlist.item>


                <flux:separator class="my-2" />

                <flux:navlist.item :href="route('customer.settings.profile')" wire:navigate icon="cog-8-tooth"
                    :current="request()->routeIs('customer.settings.*')">Settings
                </flux:navlist.item>


                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <flux:navlist.item type="submit" icon="arrow-right-start-on-rectangle"
                        class="text-red-500! cursor-pointer hover:bg-red-50!">Logout
                    </flux:navlist.item>
                </form>

            </flux:navlist>
        </div>
    </div>
</aside>
