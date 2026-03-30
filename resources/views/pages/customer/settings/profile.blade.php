<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\{Layout, Computed};
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public ?string $phone_number = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number;
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated');
    }

    public function resendVerificationEmail(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && !Auth::user()->hasVerifiedEmail();
    }
}; ?>

<x-customer-settings-layout heading="Profile" subheading="Update your personal information">
    <form wire:submit="save" class="space-y-6">
        @if ($this->hasUnverifiedEmail)
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Email not verified') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Your email address is not verified.') }}
                    <button type="button" wire:click="resendVerificationEmail" class="underline hover:no-underline">
                        {{ __('Click here to resend the verification email.') }}
                    </button>
                </flux:callout.text>

                @if (session('status') === 'verification-link-sent')
                    <flux:callout.text class="text-green-600 dark:text-green-400 font-medium">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </flux:callout.text>
                @endif
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <flux:input wire:model="name" :label="__('Name')" type="text" required />
            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required />
                @if ($this->hasUnverifiedEmail)
                    <flux:text size="xs" class="text-amber-600 mt-1">{{ __('Unverified') }}</flux:text>
                @endif
            </div>
            <flux:input wire:model="phone_number" :label="__('Phone Number')" type="tel" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>

            <x-action-message on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</x-customer-settings-layout>
