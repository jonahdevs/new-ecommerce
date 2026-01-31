<x-layouts::guest>
    <!-- Breadcrumb -->
    <div class="container mx-auto px-4 py-4">
        @isset($breadcrumbs)
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('home') }}">
                    <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                    Home
                </flux:breadcrumbs.item>

                @foreach ($breadcrumbs as $breadcrumb)
                    @if ($loop->last)
                        <flux:breadcrumbs.item>{{ $breadcrumb['title'] }}</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}
                        </flux:breadcrumbs.item>
                    @endif
                @endforeach
            </flux:breadcrumbs>
        @endisset

        <div class="flex flex-col lg:flex-row gap-6 mt-4">

            <!-- Sidebar -->
            <div class="w-full lg:w-64 shrink-0 bg-white rounded-lg border py-6 px-4 sticky! top-44! lg:self-start">
                <!-- User Profile Card -->
                <div class="flex items-center flex-col justify-center gap-3 mb-4 pb-2 border-b">
                    @if (auth()->user()->avatar)
                        <flux:avatar circle class="size-24" src="{{ auth()->user()->avatar }}" />
                    @else
                        <flux:avatar circle class="size-24" name="{{ auth()->user()->name }}" />
                    @endif

                    <p class="text-zinc-600 text-sm truncate max-w-full">{{ auth()->user()->email }}</p>
                </div>

                <flux:navlist class="w-full text-xl [&_svg]:w-7 [&_svg]:h-7">
                    <flux:navlist.item href="#" icon="user" wire:navigate>My Account
                    </flux:navlist.item>
                    <flux:navlist.item href="#" icon="package" wire:navigate>
                        Orders</flux:navlist.item>
                    <flux:navlist.item href="#" icon="envelope" badge="12">Inbox</flux:navlist.item>
                    <flux:navlist.item href="#">Pending
                        Reviews
                    </flux:navlist.item>
                    <flux:navlist.item href="#" icon="eye">Recently Viewed</flux:navlist.item>
                    <flux:navlist.item href="#" icon="heart" badge="#">Favorite
                        Items
                    </flux:navlist.item>

                    <flux:separator class="my-2" />

                    <flux:navlist.item href="#" icon="cog-8-tooth">
                        Settings
                    </flux:navlist.item>
                    <flux:navlist.item href="#" wire:navigate icon="book-open">
                        Address Book
                    </flux:navlist.item>
                    <flux:navlist.item href="#" wire:navigate icon="newspaper">
                        Newsletter Preference</flux:navlist.item>

                    <flux:separator class="my-2" />
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <flux:navlist.item type="submit" icon="arrow-right-start-on-rectangle"
                            class="!hover:bg-red-100 !hover:text-red-500 cursor-pointer">
                            Logout
                        </flux:navlist.item>
                    </form>
                </flux:navlist>
            </div>

            <!-- Main Content -->
            <main class="flex-1 pb-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts::guest>
