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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        {{-- Account Details (Read-only) --}}
        <x-customer.card title="Account" titleEm="Details">
            <x-slot:icon>
                <flux:icon.user />
            </x-slot:icon>
            <x-slot:action>

                <a href=""
                    class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-primary hover:opacity-70 transition-opacity">
                    <flux:icon.cog-6-tooth class="w-3.5 h-3.5" />
                    Edit in Settings
                </a>
            </x-slot:action>

            {{-- Avatar + Name --}}
            <div class="flex items-center gap-3.5 pb-4 mb-4 border-b border-zinc-200">
                @if ($this->user->avatar)
                    <flux:avatar circle class="size-12 shrink-0" src="{{ $this->user->avatar }}" />
                @else
                    <flux:avatar circle class="size-12 shrink-0" name="{{ $this->user->name }}" />
                @endif

                <div>
                    <div class="text-[15px] font-bold text-on-surface mb-0.5">{{ $user->name }}</div>
                    <div class="text-[11px] text-on-surface-variant">Member since {{ $user->created_at->format('F Y') }}</div>
                </div>
            </div>

            {{-- Details --}}
            <div class="mb-4">
                <div class="text-[10px] font-bold tracking-widest uppercase text-on-surface-variant mb-1">Email Address</div>
                <div class="text-[14px] font-semibold text-on-surface">{{ $user->email }}</div>
            </div>
            <div>
                <div class="text-[10px] font-bold tracking-widest uppercase text-on-surface-variant mb-1">Phone Number</div>
                <div class="text-[14px] font-semibold text-on-surface">{{ $user->phone_number ?? 'Not set' }}</div>
            </div>
        </x-customer.card>

        {{-- Address Book Preview --}}
        <x-customer.card title="Address" titleEm="Book">
            <x-slot:icon>
                <flux:icon.map-pin />
            </x-slot:icon>
            <x-slot:action>
                <a href="{{ route('customer.address-book.index') }}"
                    class="flex items-center gap-1.5 text-xs font-bold tracking-wider uppercase text-primary hover:opacity-70 transition-opacity">
                    <flux:icon.chevron-right class="w-3.5 h-3.5 stroke-2" />
                    Manage
                </a>
            </x-slot:action>

            @if ($user->defaultAddress)
                <div class="mb-1.5">
                    <span
                        class="inline-block text-[9px] font-extrabold tracking-widest uppercase px-2 py-0.5 bg-brand-primary text-white">Default</span>
                </div>
                <div class="mb-4">
                    <div class="text-[10px] font-bold tracking-widest uppercase text-on-surface-variant mb-1">Shipping Address
                    </div>
                    <div class="text-[14px] font-semibold text-on-surface">{{ $user->defaultAddress->full_name }}</div>
                </div>
                <div class="text-[12px] text-on-surface-variant leading-[1.7]">
                    {{ $user->defaultAddress->address }}<br>
                    {{ $user->defaultAddress->subCounty?->name }}, {{ $user->defaultAddress->county?->name }}<br>
                    {{ $user->defaultAddress->phone_number }}
                </div>
            @else
                <div class="text-[13px] text-on-surface-variant italic mb-4">No default address set.</div>
                <flux:button variant="customer-primary" href="{{ route('customer.address-book.index') }}" wire:navigate
                    size="customer">
                    <flux:icon.plus class="w-3.5 h-3.5" />
                    Add Address
                </flux:button>
            @endif
        </x-customer.card>
    </div>

    {{-- Quick Settings Links --}}
    <x-customer.card title="Settings" titleEm="Quick Links">
        <x-slot:icon>
            <flux:icon.cog-6-tooth />
        </x-slot:icon>

        <a href="#" wire:navigate
            class="flex items-center justify-between px-5 py-3.5 border-b border-zinc-200 transition-colors hover:bg-zinc-50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-zinc-100 flex items-center justify-center shrink-0">
                    <flux:icon.user class="w-4 h-4 text-on-surface-variant" />
                </div>
                <div>
                    <div class="text-[13px] font-bold text-on-surface">Profile Settings</div>
                    <div class="text-[11px] text-on-surface-variant">Update your personal information</div>
                </div>
            </div>
        </a>

        <a href="#" wire:navigate
            class="flex items-center justify-between px-5 py-3.5 border-b border-zinc-200 transition-colors hover:bg-zinc-50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-zinc-100 flex items-center justify-center shrink-0">
                    <flux:icon.lock-closed class="w-4 h-4 text-on-surface-variant" />
                </div>
                <div>
                    <div class="text-[13px] font-bold text-on-surface">Password & Security</div>
                    <div class="text-[11px] text-on-surface-variant">Change password and manage 2FA</div>
                </div>
            </div>
            @if (!$user->two_factor_confirmed_at)
                <span
                    class="text-[9px] font-extrabold tracking-wider uppercase px-2 py-0.5 bg-orange-100 text-brand-primary border border-orange-200">Action
                    Needed</span>
            @endif
        </a>

        <a href="#" wire:navigate
            class="flex items-center justify-between px-5 py-3.5 border-b border-zinc-200 transition-colors hover:bg-zinc-50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-zinc-100 flex items-center justify-center shrink-0">
                    <flux:icon.bell class="w-4 h-4 text-on-surface-variant" />
                </div>
                <div>
                    <div class="text-[13px] font-bold text-on-surface">Notifications</div>
                    <div class="text-[11px] text-on-surface-variant">Manage email, SMS and push preferences</div>
                </div>
            </div>
        </a>

        <a href="#" wire:navigate
            class="flex items-center justify-between px-5 py-3.5 border-b-0 transition-colors hover:bg-zinc-50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-zinc-100 flex items-center justify-center shrink-0">
                    <flux:icon.shield-check class="w-4 h-4 text-on-surface-variant" />
                </div>
                <div>
                    <div class="text-[13px] font-bold text-on-surface">Privacy & Data</div>
                    <div class="text-[11px] text-on-surface-variant">Control your data and privacy settings</div>
                </div>
            </div>
        </a>
    </x-customer.card>
</div>
