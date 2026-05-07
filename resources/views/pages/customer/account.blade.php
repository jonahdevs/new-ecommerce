<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use App\Models\User;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer')] class extends Component {
    public User $user;

    public function mount()
    {
        SEOMeta::setRobots('noindex,nofollow');
        $this->user = auth()->user();
    }

    #[Computed]
    public function favoriteProductsCount()
    {
        return $this->user->wishlistProducts()->count();
    }

    #[Computed]
    public function totalOrders()
    {
        return $this->user->orders()->count();
    }

    #[Computed]
    public function totalReviews()
    {
        return $this->user->reviews()->count();
    }

    #[Computed]
    public function productReturns()
    {
        return $this->user->orders()->where('status', 'returned')->count();
    }
};
?>

<div class="space-y-4">
    <flux:card class="grid grid-cols-2 md:grid-cols-4 gap-6 rounded-md">
        {{-- Favorite Products --}}
        <div class="flex items-center gap-4 group">
            <div
                class="shrink-0 p-3 rounded-md bg-rose-100 text-rose-600 
                    group-hover:scale-105 transition">
                <flux:icon.heart class="w-5 h-5" />
            </div>
            <div>
                <flux:text class="text-zinc-500 text-xs! sm:text-sm!" size="sm">Favorite Products</flux:text>
                <flux:heading class="text-lg! sm:text-xl! font-semibold">
                    {{ $this->favoriteProductsCount }}
                </flux:heading>
            </div>
        </div>

        {{-- Total Orders --}}
        <div class="flex items-center gap-4 group">
            <div
                class="shrink-0 p-3 rounded-md bg-blue-100 text-blue-600 
                    group-hover:scale-105 transition">
                <flux:icon.package class="w-5 h-5" />
            </div>
            <div>
                <flux:text class="text-zinc-500 text-xs! sm:text-sm!" size="sm">Total Orders</flux:text>
                <flux:heading class="text-lg! sm:text-xl! font-semibold">
                    {{ $this->totalOrders }}
                </flux:heading>
            </div>
        </div>

        {{-- Total Reviews --}}
        <div class="flex items-center gap-4 group">
            <div
                class="shrink-0 p-3 rounded-md bg-amber-100 text-amber-600 
                    group-hover:scale-105 transition">
                <flux:icon.star class="w-5 h-5" />
            </div>
            <div>
                <flux:text class="text-zinc-500 text-xs! sm:text-sm!" size="sm">Total Reviews</flux:text>
                <flux:heading class="text-lg! sm:text-xl! font-semibold">
                    {{ $this->totalReviews }}
                </flux:heading>
            </div>
        </div>

        {{-- Product Returns --}}
        <div class="flex items-center gap-4 group">
            <div
                class="shrink-0 p-3 rounded-md bg-zinc-100 text-zinc-600 
                    group-hover:scale-105 transition">
                <flux:icon.arrow-path-rounded-square class="w-5 h-5" />
            </div>
            <div>
                <flux:text class="text-zinc-500 text-xs! sm:text-sm!" size="sm">Product Returns</flux:text>
                <flux:heading class="text-lg! sm:text-xl! font-semibold">
                    {{ $this->productReturns }}
                </flux:heading>
            </div>
        </div>

    </flux:card>

    <flux:card class="p-0 rounded-md">
        <div class="border-b px-3 py-2">
            <flux:heading size="lg" class="text-base! sm:text-lg!">Account Overview</flux:heading>
        </div>
        <div class="p-5 grid grid-cols-2 gap-4">
            <div class="border rounded-md">
                <div class="px-4 py-2 border-b">
                    <flux:heading class="font-medium text-zinc-600 text-xs! sm:text-sm!">Account Details</flux:heading>
                </div>
                <div class="p-4 text-xs! sm:text-sm! space-y-1">
                    <flux:text>{{ $this->user->name }}</flux:text>
                    <flux:text>{{ $this->user->email }}</flux:text>
                    <flux:text>{{ $this->user->phone_number }}</flux:text>
                </div>
            </div>

            <div class="border rounded-md">
                <div class="px-4 py-2 border-b flex justify-between items-center">
                    <flux:heading class="font-medium text-zinc-600 text-xs! sm:text-sm!">Address Book</flux:heading>

                    <flux:button icon="pencil" size="xs" class="cursor-pointer"
                        href="{{ $user->defaultAddress ? route('customer.address-book.edit', $user->defaultAddress?->id) : route('customer.address-book.create') }}">
                    </flux:button>
                </div>
                <div class="p-4 text-xs! sm:text-sm! space-y-1">
                    @if ($user->defaultAddress)
                        <flux:heading class="text-xs! sm:text-sm!">Default Shipping Address</flux:heading>

                        <flux:text class="mt-2">{{ $this->user->defaultAddress?->full_name }}
                        </flux:text>

                        <flux:text>{{ $this->user->defaultAddress?->address }}</flux:text>

                        <flux:text>
                            {{ $this->user->defaultAddress?->area?->name . ', ' . $this->user->defaultAddress?->county?->name }}
                        </flux:text>

                        <flux:text>
                            {{ implode(' / ', array_filter([$this->user->defaultAddress?->phone_number, $this->user->defaultAddress?->alternative_phone_number])) }}
                        </flux:text>
                    @endif
                </div>
            </div>

            <div class="border rounded-md">
                <div class="px-4 py-2 border-b">
                    <flux:heading class="font-medium text-zinc-600 text-xs! sm:text-sm!">Newsletter preference
                    </flux:heading>
                </div>

                <div class="p-4 text-xs! sm:text-sm!">
                    <flux:text class="mb-3">Manage your email communications to stay updated with the latest news and
                        offers.
                    </flux:text>

                    <flux:link href="{{ route('customer.settings.preferences') }}" wire:navigate
                        class="text-secondary hover:underline ">Edit
                        newsletter
                        preference
                    </flux:link>
                </div>
            </div>
        </div>
    </flux:card>
</div>
