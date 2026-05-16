<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer-settings'), Title('Privacy & Data')] class extends Component {
    public string $delete_password = '';

    public bool $confirm_delete = false;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
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
            'user' => $user->only(['id', 'name', 'display_name', 'email', 'phone_number', 'date_of_birth', 'created_at', 'newsletter_subscribed']),
            'addresses' => $user->addresses->toArray(),
            'orders' => $user->orders->toArray(),
            'reviews' => $user->reviews->toArray(),
            'wishlist' => $user->wishlistItems->toArray(),
            'preferences' => [
                'notifications' => $user->notification_preferences,
                'privacy' => $user->privacy_preferences,
            ],
        ];

        $filename = "shopsmart-data-export-{$user->id}-" . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(fn() => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $filename, ['Content-Type' => 'application/json']);
    }

    public function deleteAccount()
    {
        $this->validate(
            [
                'delete_password' => ['required', 'string', 'current_password'],
                'confirm_delete' => ['accepted'],
            ],
            [
                'delete_password.current_password' => __('The password you entered is incorrect.'),
                'confirm_delete.accepted' => __('Please confirm that you want to delete your account.'),
            ],
        );

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
    {{-- Data Management --}}
    <x-customer.settings-card title="Data" titleEm="Management">
        <x-slot:icon>
            <flux:icon.cube />
        </x-slot:icon>

        <x-customer.privacy-row title="Download Your Data"
            description="Download a JSON export of your personal data, orders, addresses, reviews and preferences.">
            <flux:button type="button" wire:click="downloadData" variant="customer-outline" size="customer">
                <span wire:loading.remove wire:target="downloadData">Download</span>
                <span wire:loading wire:target="downloadData">Preparing...</span>
            </flux:button>
        </x-customer.privacy-row>

        <x-customer.privacy-row title="Data Retention"
            description="Your data is retained for 7 years after account closure as required by law" :lastItem="true">
            <span
                class="text-[10px] font-bold px-2 py-0.5 bg-zinc-100 text-zinc-500 border border-zinc-200 tracking-wider uppercase">7
                Years</span>
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
                <x-customer.form-field label="{{ __('Confirm your password') }}" name="delete_password">
                    <input type="password" wire:model="delete_password" placeholder="••••••••"
                        class="customer-input max-w-md border-red-300 focus:border-red-500 focus:ring-red-500/8">
                </x-customer.form-field>

                <label class="flex items-start gap-2 text-[12px] text-zinc-700 cursor-pointer">
                    <input type="checkbox" wire:model="confirm_delete" class="mt-0.5 accent-red-500">
                    <span>{{ __('I understand this action will sign me out and disable my account.') }}</span>
                </label>
                @error('confirm_delete')
                    <span class="text-[11px] text-red-500 font-semibold block">{{ $message }}</span>
                @enderror

                <flux:button type="submit" variant="customer-danger" size="customer-lg"
                    wire:confirm="This will sign you out and disable your account. Are you sure?"
                    class="bg-red-500! border-red-500! text-white! hover:bg-red-600! hover:border-red-600!">
                    <span wire:loading.remove wire:target="deleteAccount">Delete My Account</span>
                    <span wire:loading wire:target="deleteAccount">Deleting...</span>
                </flux:button>
            </form>
        </div>
    </x-customer.settings-card>
</div>
