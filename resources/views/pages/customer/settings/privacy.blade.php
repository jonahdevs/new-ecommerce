<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer-settings'), Title('Privacy & Data')] class extends Component
{
    /**
     * Per-flag privacy preferences. Persisted as JSON on users.privacy_preferences.
     *
     * @var array<string, bool>
     */
    public array $prefs = [];

    public string $delete_password = '';

    public bool $confirm_delete = false;

    public static function defaults(): array
    {
        return [
            'profile_public' => true,
            'search_indexable' => false,
            'activity_visible' => true,
        ];
    }

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');

        $stored = Auth::user()->privacy_preferences ?? [];
        $this->prefs = array_replace(self::defaults(), $stored);
    }

    public function togglePref(string $key): void
    {
        if (! array_key_exists($key, $this->prefs)) {
            return;
        }

        $this->prefs[$key] = ! $this->prefs[$key];

        Auth::user()->update(['privacy_preferences' => $this->prefs]);

        $this->dispatch('toast', message: __('Preference updated'), type: 'success');
    }

    /**
     * Stream a JSON export of the user's personal data on the spot.
     * For larger users this should be queued + emailed; this synchronous version
     * is fine for demo / typical customer-sized data.
     */
    public function downloadData()
    {
        $user = Auth::user()->load(['addresses', 'orders.items', 'reviews', 'wishlistItems']);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'user' => $user->only([
                'id', 'name', 'display_name', 'email', 'phone_number',
                'date_of_birth', 'created_at', 'newsletter_subscribed',
            ]),
            'addresses' => $user->addresses->toArray(),
            'orders' => $user->orders->toArray(),
            'reviews' => $user->reviews->toArray(),
            'wishlist' => $user->wishlistItems->toArray(),
            'preferences' => [
                'notifications' => $user->notification_preferences,
                'privacy' => $user->privacy_preferences,
            ],
        ];

        $filename = "shopsmart-data-export-{$user->id}-".now()->format('Ymd-His').'.json';

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function deleteAccount()
    {
        $this->validate([
            'delete_password' => ['required', 'string', 'current_password'],
            'confirm_delete' => ['accepted'],
        ], [
            'delete_password.current_password' => __('The password you entered is incorrect.'),
            'confirm_delete.accepted' => __('Please confirm that you want to delete your account.'),
        ]);

        $user = Auth::user();

        // Detach the avatar file from the public disk so it doesn't dangle.
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        Auth::logout();

        // SoftDeletes trait on the User model preserves orders/reviews
        // for record-keeping while removing the customer's access.
        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(route('home'), navigate: false);
    }
}; ?>

<div class="flex flex-col gap-5">
    {{-- Privacy Settings --}}
    <x-customer.settings-card title="Privacy" titleEm="Settings">
        <x-slot:icon>
            <flux:icon.shield-check />
        </x-slot:icon>

        @php
            $rows = [
                ['key' => 'profile_public', 'title' => 'Profile Visibility', 'description' => 'Control who can see your profile information and reviews', 'on' => 'Public', 'off' => 'Private'],
                ['key' => 'search_indexable', 'title' => 'Search Engine Indexing', 'description' => 'Allow search engines to index your public profile and reviews', 'on' => 'Indexable', 'off' => 'Hidden'],
                ['key' => 'activity_visible', 'title' => 'Activity Status', 'description' => 'Show when you\'re active on the platform', 'on' => 'Visible', 'off' => 'Hidden'],
            ];
        @endphp

        @foreach ($rows as $i => $row)
            @php $on = $prefs[$row['key']] ?? false; @endphp
            <x-customer.privacy-row :title="$row['title']" :description="$row['description']" :lastItem="$i === count($rows) - 1">
                <span @class([
                    'text-[10px] font-bold px-2 py-0.5 border tracking-wider uppercase',
                    'bg-green-100 text-green-700 border-green-200' => $on,
                    'bg-zinc-100 text-zinc-500 border-zinc-200' => ! $on,
                ])>{{ $on ? $row['on'] : $row['off'] }}</span>
                <button type="button" wire:click="togglePref('{{ $row['key'] }}')"
                    class="border-[1.5px] border-zinc-200 px-3 py-1 font-barlow-condensed text-[11px] font-extrabold tracking-wider uppercase transition-all hover:border-zinc-950 hover:bg-zinc-950 hover:text-white cursor-pointer">
                    {{ $on ? 'Disable' : 'Enable' }}
                </button>
            </x-customer.privacy-row>
        @endforeach
    </x-customer.settings-card>

    {{-- Data Management --}}
    <x-customer.settings-card title="Data" titleEm="Management">
        <x-slot:icon>
            <flux:icon.cube />
        </x-slot:icon>

        <x-customer.privacy-row title="Download Your Data" description="Download a JSON export of your personal data, orders, addresses, reviews and preferences.">
            <button type="button" wire:click="downloadData"
                class="border-[1.5px] border-zinc-950 px-3.5 py-1.5 font-barlow-condensed text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-zinc-950 hover:text-white cursor-pointer">
                <span wire:loading.remove wire:target="downloadData">Download</span>
                <span wire:loading wire:target="downloadData">Preparing...</span>
            </button>
        </x-customer.privacy-row>

        <x-customer.privacy-row title="Data Retention" description="Your data is retained for 7 years after account closure as required by law" :lastItem="true">
            <span class="text-[10px] font-bold px-2 py-0.5 bg-zinc-100 text-zinc-500 border border-zinc-200 tracking-wider uppercase">7 Years</span>
        </x-customer.privacy-row>
    </x-customer.settings-card>

    {{-- Danger Zone --}}
    <x-customer.settings-card title="Delete" titleEm="Account" danger>
        <x-slot:icon>
            <flux:icon.information-circle />
        </x-slot:icon>

        <div class="px-5 py-5">
            <p class="text-[13px] text-zinc-700 mb-4">
                {{ __('Deleting your account signs you out and removes your access. Orders and reviews are retained in line with the data-retention policy above for legal/accounting purposes, then anonymised.') }}
            </p>

            <form wire:submit="deleteAccount" class="space-y-3">
                <div>
                    <label class="block text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500 mb-1.5">{{ __('Confirm your password') }}</label>
                    <input type="password" wire:model="delete_password" placeholder="••••••••"
                        class="w-full max-w-md border-[1.5px] border-red-300 px-3 py-2.5 text-[13px] font-medium outline-none transition-all focus:border-red-500 focus:ring-[3px] focus:ring-red-500/8">
                    @error('delete_password')
                        <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <label class="flex items-start gap-2 text-[12px] text-zinc-700 cursor-pointer">
                    <input type="checkbox" wire:model="confirm_delete" class="mt-0.5 accent-red-500">
                    <span>{{ __('I understand this action will sign me out and disable my account.') }}</span>
                </label>
                @error('confirm_delete')
                    <span class="text-[11px] text-red-500 font-semibold block">{{ $message }}</span>
                @enderror

                <button type="submit" wire:confirm="This will sign you out and disable your account. Are you sure?"
                    class="border-[1.5px] border-red-500 bg-red-500 text-white px-4 py-2 font-barlow-condensed text-[12px] font-extrabold tracking-wider uppercase transition-all hover:bg-red-600 hover:border-red-600 cursor-pointer">
                    <span wire:loading.remove wire:target="deleteAccount">Delete My Account</span>
                    <span wire:loading wire:target="deleteAccount">Deleting...</span>
                </button>
            </form>
        </div>
    </x-customer.settings-card>
</div>
