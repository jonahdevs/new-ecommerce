<?php

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.customer-settings'), Title('Profile Settings')] class extends Component {
    use WithFileUploads;

    public string $name = '';

    public ?string $display_name = '';

    public string $email = '';

    public ?string $phone_number = '';

    public ?string $date_of_birth = '';

    public $avatar; // Livewire TemporaryUploadedFile

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->display_name = $user->display_name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number;
        $this->date_of_birth = $user->date_of_birth?->format('Y-m-d');
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('toast', message: __('Profile updated successfully'), type: 'success');
    }

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        // Delete the previous avatar (if any) so we don't accumulate orphans.
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store under avatars/<user-id>-<uniqid>.<ext> on the public disk.
        $path = $this->avatar->storePublicly('avatars', 'public');

        $user->update(['avatar' => $path]);

        $this->avatar = null;

        $this->dispatch('toast', message: __('Avatar updated'), type: 'success');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        $this->dispatch('toast', message: __('Avatar removed'), type: 'success');
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

    #[Computed]
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim(Auth::user()->name ?? ''));
        $first = strtoupper(substr($parts[0] ?? '', 0, 1));
        $last = count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) : '';

        return $first . $last;
    }
}; ?>

@php
    $inputClass =
        'w-full border-[1.5px] border-zinc-200 px-3 py-2.5 text-[13px] font-medium outline-none transition-all focus:border-brand-primary focus:ring-[3px] focus:ring-brand-primary/8';
    $labelClass = 'block text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500 mb-1.5';
@endphp

<div class="flex flex-col gap-5">
    {{-- Profile Photo --}}
    <x-customer.settings-card title="Profile" titleEm="Photo">
        <x-slot:icon>
            <flux:icon.user />
        </x-slot:icon>

        <div class="flex items-center gap-6 px-5 py-5">
            <label for="avatarInput"
                class="relative w-20 h-20 rounded-full bg-zinc-950 text-white font-barlow-condensed text-[26px] font-black flex items-center justify-center shrink-0 cursor-pointer hover:brightness-75 transition-all overflow-hidden">
                @if (auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                        class="w-full h-full object-cover" />
                @else
                    {{ $this->initials }}
                @endif
                <div
                    class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-brand-primary flex items-center justify-center border-2 border-white">
                    <flux:icon.pencil-square class="w-[11px] h-[11px] text-white" />
                </div>
            </label>

            <div class="flex-1">
                <div class="text-[16px] font-bold text-zinc-950 mb-0.5">{{ auth()->user()->name }}</div>
                <div class="text-[12px] text-zinc-500 mb-2.5">{{ auth()->user()->email }}</div>

                <div class="flex items-center gap-2">
                    <label for="avatarInput"
                        class="inline-flex items-center gap-1.5 border-[1.5px] border-zinc-950 px-3.5 py-1.5 font-barlow-condensed text-[12px] font-extrabold tracking-wider uppercase transition-all hover:bg-zinc-950 hover:text-white cursor-pointer">
                        <flux:icon.arrow-up-tray class="w-3.5 h-3.5" />
                        <span wire:loading.remove wire:target="avatar">Upload Photo</span>
                        <span wire:loading wire:target="avatar">Uploading...</span>
                    </label>
                    @if (auth()->user()->avatar)
                        <button type="button" wire:click="removeAvatar"
                            class="inline-flex items-center gap-1.5 border-[1.5px] border-red-500 text-red-500 px-3.5 py-1.5 font-barlow-condensed text-[12px] font-extrabold tracking-wider uppercase transition-all hover:bg-red-500 hover:text-white cursor-pointer">
                            Remove
                        </button>
                    @endif
                </div>

                <input type="file" id="avatarInput" wire:model="avatar" accept="image/*" class="hidden">
                <div class="text-[11px] text-zinc-500 mt-2">JPG, PNG, GIF or WEBP. Max 2MB.</div>
                @error('avatar')
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </x-customer.settings-card>

    {{-- Personal Information --}}
    <x-customer.settings-card title="Personal" titleEm="Information">
        <x-slot:icon>
            <flux:icon.pencil-square />
        </x-slot:icon>

        @if ($this->hasUnverifiedEmail)
            <div class="flex items-start gap-3 mx-5 mt-5 p-3 bg-amber-50 border border-amber-200 rounded-sm">
                <flux:icon.exclamation-triangle class="size-4 shrink-0 mt-0.5 text-amber-500" />
                <div>
                    <div class="text-[13px] font-bold text-amber-900">{{ __('Email not verified') }}</div>
                    <div class="text-[12px] text-amber-800">
                        {{ __('Your email address is not verified.') }}
                        <button type="button" wire:click="resendVerificationEmail"
                            class="underline font-bold hover:no-underline cursor-pointer">{{ __('Resend verification email') }}</button>
                    </div>
                    @if (session('status') === 'verification-link-sent')
                        <div class="text-[12px] text-green-700 font-bold mt-1">
                            {{ __('A new verification link has been sent.') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <form wire:submit="save" class="px-5 py-5">
            <div class="mb-3.5">
                <label class="{{ $labelClass }}">{{ __('Full Name') }} *</label>
                <input type="text" wire:model="name" class="{{ $inputClass }}" required placeholder="John Doe">
                @error('name')
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3.5">
                <label class="{{ $labelClass }}">{{ __('Display Name') }}</label>
                <input type="text" wire:model="display_name" class="{{ $inputClass }}" placeholder="Optional">
                <div class="text-[11px] text-zinc-500 mt-1">
                    {{ __('How your name appears on reviews. Defaults to your full name when blank.') }}</div>
                @error('display_name')
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3.5">
                <label class="{{ $labelClass }}">{{ __('Email Address') }} *</label>
                <input type="email" wire:model="email" class="{{ $inputClass }}" required>
                <div class="text-[11px] text-zinc-500 mt-1">
                    {{ __('A verification email will be sent if you change this.') }}</div>
                @error('email')
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <div>
                    <label class="{{ $labelClass }}">{{ __('Phone Number') }}</label>
                    <input type="tel" wire:model="phone_number" class="{{ $inputClass }}"
                        placeholder="+254 712 345 678">
                    @error('phone_number')
                        <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('Date of Birth') }}</label>
                    <input type="date" wire:model="date_of_birth" class="{{ $inputClass }}"
                        max="{{ now()->format('Y-m-d') }}">
                    @error('date_of_birth')
                        <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-2.5 mt-5 pt-4 border-t border-zinc-200">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-brand-primary text-white px-6 py-2.5 font-barlow-condensed text-[13px] font-extrabold tracking-wider uppercase transition-colors hover:bg-[#e03d00] cursor-pointer">
                    <span wire:loading.remove wire:target="save">{{ __('Save Changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </button>
            </div>
        </form>
    </x-customer.settings-card>

    {{-- Account Info --}}
    <x-customer.settings-card title="Account" titleEm="Info">
        <x-slot:icon>
            <flux:icon.calendar />
        </x-slot:icon>

        <div class="grid grid-cols-1 md:grid-cols-2 px-5 py-5">
            <div class="py-2.5 md:pr-5 md:border-r md:border-zinc-200">
                <div class="text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500">Member Since</div>
                <div class="text-[14px] font-bold text-zinc-950 mt-1">{{ auth()->user()->created_at->format('F Y') }}
                </div>
            </div>
            <div class="py-2.5 md:pl-5">
                <div class="text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500">Account Type</div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-[14px] font-bold text-zinc-950">Standard</span>
                    <span
                        class="text-[10px] font-extrabold px-2 py-0.5 bg-zinc-100 text-zinc-500 border border-zinc-200 tracking-wider uppercase">Free</span>
                </div>
            </div>
        </div>
    </x-customer.settings-card>
</div>
