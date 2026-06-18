<?php

use App\Concerns\ProfileValidationRules;
/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use App\Support\CountryCodes;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::settings')] #[Title('Profile')] class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone_country_code = '+254';
    public string $phone_local = '';
    public bool $embedded = false;
    public $avatar = null;

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->name  = Auth::user()->name;
        $this->email = Auth::user()->email;

        [$this->phone_country_code, $this->phone_local] = CountryCodes::parse(
            Auth::user()->phone ?? ''
        );
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $this->validate([
            'phone_country_code' => ['required', 'string', 'max:10'],
            'phone_local'        => ['nullable', 'string', 'max:20'],
        ]);

        $validated = $this->validate($this->profileRules($user->id));

        $validated['phone'] = filled($this->phone_local)
            ? $this->phone_country_code . ltrim($this->phone_local, '0')
            : null;

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $this->avatar->storePublicly('avatars', 'public');

        $user->update(['avatar' => $path]);

        $this->avatar = null;

        Flux::toast(variant: 'success', text: __('Avatar updated.'));
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        Flux::toast(variant: 'success', text: __('Avatar removed.'));
    }

    /* @chisel-email-verification */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
    /* @end-chisel-email-verification */
}; ?>

@if (!$embedded)
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Profile</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush
@endif

<section class="w-full">
    @include('partials.settings-heading', ['embedded' => $embedded])

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::account.settings.layout :embedded="$embedded" :card="false">

        <div class="space-y-4">

            {{-- Avatar --}}
            <flux:card class="p-0">
                <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3 dark:border-zinc-700">
                    <flux:icon.user variant="outline" class="size-4 text-zinc-600 dark:text-zinc-400" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Profile Photo</flux:heading>
                </div>

                <div class="flex items-center gap-5 p-5">
                    <div class="relative shrink-0">
                        <label for="avatarInput" class="cursor-pointer">
                            @if (Auth::user()->avatar)
                                <flux:avatar circle class="size-20" src="{{ Storage::disk('public')->url(Auth::user()->avatar) }}" />
                            @else
                                <flux:avatar circle class="size-20" name="{{ Auth::user()->name }}" />
                            @endif
                            <div class="absolute bottom-0 right-0 flex size-6 items-center justify-center rounded-full border-2 border-white bg-zinc-700 dark:border-zinc-900">
                                <flux:icon.pencil-square class="size-3 text-white" />
                            </div>
                        </label>
                    </div>

                    <div class="flex-1">
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ Auth::user()->name }}</div>
                        <div class="mb-3 text-sm text-zinc-500">{{ Auth::user()->email }}</div>

                        <div class="flex items-center gap-2">
                            <flux:button tag="label" for="avatarInput" variant="filled" size="sm" icon="arrow-up-tray">
                                <span wire:loading.remove wire:target="avatar">Upload photo</span>
                                <span wire:loading wire:target="avatar">Uploading…</span>
                            </flux:button>

                            @if (Auth::user()->avatar)
                                <flux:button type="button" wire:click="removeAvatar" variant="ghost" size="sm">
                                    Remove
                                </flux:button>
                            @endif
                        </div>

                        <input type="file" id="avatarInput" wire:model="avatar" accept="image/*" class="hidden">
                        <p class="mt-2 text-xs text-zinc-400">JPG, PNG, GIF or WEBP. Max 2MB.</p>

                        @error('avatar')
                            <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </flux:card>

            {{-- Profile Information --}}
            <form wire:submit="updateProfileInformation">
                <flux:card class="p-0">
                    <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3 dark:border-zinc-700">
                        <flux:icon.pencil-square variant="outline" class="size-4 text-zinc-600 dark:text-zinc-400" />
                        <flux:heading size="sm" class="uppercase tracking-wide">Personal Information</flux:heading>
                    </div>

                    <div class="space-y-5 p-5">
                        <flux:input wire:model="name" :label="__('Full name')" type="text" required autofocus autocomplete="name" />

                        <div>
                            <flux:input wire:model="email" :label="__('Email address')" type="email" required autocomplete="email" />

                            {{-- @chisel-email-verification --}}
                            @if ($this->hasUnverifiedEmail)
                                <div class="mt-3">
                                    <flux:text>
                                        {{ __('Your email address is unverified.') }}
                                        <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                                            {{ __('Re-send verification email.') }}
                                        </flux:link>
                                    </flux:text>

                                    @if (session('status') === 'verification-link-sent')
                                        <flux:text class="mt-2 font-medium text-green-600! dark:text-green-400!">
                                            {{ __('A new verification link has been sent to your email address.') }}
                                        </flux:text>
                                    @endif
                                </div>
                            @endif
                            {{-- @end-chisel-email-verification --}}
                        </div>

                        <flux:field>
                            <flux:label>WhatsApp / Phone number</flux:label>
                            <flux:input.group>
                                <flux:select wire:model="phone_country_code" class="w-40 shrink-0">
                                    @foreach(\App\Support\CountryCodes::all() as $country)
                                        <flux:select.option value="{{ $country['dial'] }}">
                                            {{ $country['flag'] }} {{ $country['name'] }} ({{ $country['dial'] }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input
                                    wire:model="phone_local"
                                    type="tel"
                                    placeholder="712 345 678"
                                    autocomplete="tel" />
                            </flux:input.group>
                            <flux:description>Used for WhatsApp order notifications.</flux:description>
                            <flux:error name="phone_local" />
                        </flux:field>
                    </div>
                </flux:card>

                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" type="submit" data-test="update-profile-button">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>
            </form>

        </div>

    </x-pages::account.settings.layout>
</section>
