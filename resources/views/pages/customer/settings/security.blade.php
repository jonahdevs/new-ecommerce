<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Laravel\Fortify\Features;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts.customer-settings'), Title('Password & Security')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $showCurrentPassword = false;

    public bool $showNewPassword = false;

    public bool $showConfirmPassword = false;

    // For "Sign out other devices" confirmation modal.
    public string $logout_password = '';

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation', 'showCurrentPassword', 'showNewPassword', 'showConfirmPassword']);

        $this->dispatch('toast', message: __('Password updated successfully'), type: 'success');
    }

    // ─── Two-factor ──────────────────────────────────────────────────────

    #[Computed]
    public function twoFactorEnabled(): bool
    {
        return !is_null(Auth::user()->two_factor_secret);
    }

    #[Computed]
    public function twoFactorConfirmed(): bool
    {
        return !is_null(Auth::user()->two_factor_confirmed_at);
    }

    public function enableTwoFactor(): void
    {
        Auth::user()
            ->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])
            ->save();

        Auth::user()->enableTwoFactorAuthentication();

        $this->dispatch('toast', message: __('Two-factor enabled. Scan the QR code with your authenticator app.'), type: 'success');
    }

    public function disableTwoFactor(): void
    {
        Auth::user()->disableTwoFactorAuthentication();

        $this->dispatch('toast', message: __('Two-factor authentication disabled'), type: 'success');
    }

    public function regenerateRecoveryCodes(): void
    {
        Auth::user()->generateNewRecoveryCodes();

        $this->dispatch('toast', message: __('New recovery codes generated'), type: 'success');
    }

    // ─── Active sessions ─────────────────────────────────────────────────

    /**
     * The current session's display info, parsed from the request user-agent.
     * (We don't enumerate other sessions because the configured session driver
     * is Redis — sessions aren't queryable per-user without a custom store.
     * "Sign out everywhere" still works via logoutOtherDevices.)
     */
    #[Computed]
    public function currentSession(): array
    {
        $agent = (string) Request::userAgent();

        return [
            'browser' => $this->parseBrowser($agent),
            'platform' => $this->parsePlatform($agent),
            'ip' => Request::ip(),
        ];
    }

    public function logoutOtherDevices(): void
    {
        $this->validate(
            [
                'logout_password' => ['required', 'string', 'current_password'],
            ],
            [
                'logout_password.current_password' => __('The password you entered is incorrect.'),
            ],
        );

        Auth::logoutOtherDevices($this->logout_password);

        $this->reset('logout_password');

        $this->dispatch('toast', message: __('Other browser sessions have been signed out.'), type: 'success');
    }

    private function parseBrowser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg/') => 'Microsoft Edge',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Unknown browser',
        };
    }

    private function parsePlatform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown device',
        };
    }

    public function getPasswordStrength(): array
    {
        $password = $this->password;
        $strength = 0;

        if (strlen($password) >= 8) {
            $strength += 25;
        }
        if (preg_match('/[a-z]/', $password)) {
            $strength += 25;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 25;
        }
        if (preg_match('/[0-9]/', $password) || preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 25;
        }

        [$label, $color] = match (true) {
            $strength <= 25 => ['Weak', '#e74c3c'],
            $strength <= 50 => ['Fair', '#f39c12'],
            $strength <= 75 => ['Good', '#3498db'],
            default => ['Strong', '#2ecc71'],
        };

        return ['strength' => $strength, 'label' => $label, 'color' => $color];
    }
}; ?>

@php
    $inputClass =
        'w-full border-[1.5px] border-zinc-200 px-3 py-2.5 pr-10 text-[13px] font-medium outline-none transition-all focus:border-primary focus:ring-[3px] focus:ring-primary/8';
    $labelClass = 'block text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500 mb-1.5';
    $eyeBtnClass =
        'absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer';
@endphp

<div class="flex flex-col gap-5">
    {{-- Change Password --}}
    <x-customer.settings-card title="Change" titleEm="Password">
        <x-slot:icon>
            <flux:icon.lock-closed />
        </x-slot:icon>

        <form wire:submit="updatePassword" class="px-5 py-5">
            <div class="mb-3.5">
                <label class="{{ $labelClass }}">Current Password *</label>
                <div class="relative">
                    <input :type="$wire.showCurrentPassword ? 'text' : 'password'" wire:model="current_password"
                        placeholder="Enter current password" class="{{ $inputClass }}" required>
                    <button type="button" @click="$wire.showCurrentPassword = !$wire.showCurrentPassword"
                        class="{{ $eyeBtnClass }}">
                        <flux:icon.eye class="w-4 h-4" />
                    </button>
                </div>
                @error('current_password')
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3.5">
                <label class="{{ $labelClass }}">New Password *</label>
                <div class="relative">
                    <input :type="$wire.showNewPassword ? 'text' : 'password'" wire:model.live="password"
                        placeholder="Min. 8 characters" class="{{ $inputClass }}" required>
                    <button type="button" @click="$wire.showNewPassword = !$wire.showNewPassword"
                        class="{{ $eyeBtnClass }}">
                        <flux:icon.eye class="w-4 h-4" />
                    </button>
                </div>
                @if ($password)
                    @php $strength = $this->getPasswordStrength(); @endphp
                    <div class="mt-2">
                        <div class="h-[3px] bg-zinc-200 rounded-full overflow-hidden mb-1">
                            <div class="h-full rounded-full transition-all duration-300"
                                style="width: {{ $strength['strength'] }}%; background-color: {{ $strength['color'] }}">
                            </div>
                        </div>
                        <div class="text-[10px] font-bold tracking-wider uppercase"
                            style="color: {{ $strength['color'] }}">
                            {{ $strength['label'] }}
                        </div>
                    </div>
                @endif
                @error('password')
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Confirm New Password *</label>
                <div class="relative">
                    <input :type="$wire.showConfirmPassword ? 'text' : 'password'" wire:model="password_confirmation"
                        placeholder="Repeat new password" class="{{ $inputClass }}" required>
                    <button type="button" @click="$wire.showConfirmPassword = !$wire.showConfirmPassword"
                        class="{{ $eyeBtnClass }}">
                        <flux:icon.eye class="w-4 h-4" />
                    </button>
                </div>
                @if ($password && $password_confirmation && $password !== $password_confirmation)
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">Passwords do not match.</span>
                @endif
            </div>

            <div class="flex items-center gap-2.5 mt-5 pt-4 border-t border-zinc-200">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-primary text-white px-6 py-2.5 font-serif text-[13px] font-extrabold tracking-wider uppercase transition-colors hover:bg-[#e03d00] cursor-pointer">
                    <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                    <span wire:loading wire:target="updatePassword">Updating...</span>
                </button>
            </div>
        </form>
    </x-customer.settings-card>

    {{-- Two-Factor Authentication --}}
    @if (Features::canManageTwoFactorAuthentication())
        <x-customer.settings-card title="Two-Factor" titleEm="Authentication">
            <x-slot:icon>
                <flux:icon.shield-check />
            </x-slot:icon>

            <div class="px-5 py-5">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <div class="text-[13px] font-bold text-zinc-950 mb-0.5">Authenticator App</div>
                        <div class="text-[12px] text-zinc-500">Use Google Authenticator, Authy, or 1Password to generate
                            one-time codes when signing in.</div>
                    </div>
                    <div class="shrink-0">
                        @if ($this->twoFactorConfirmed)
                            <span
                                class="text-[10px] font-extrabold px-2 py-0.5 bg-green-100 text-green-700 border border-green-200 tracking-wider uppercase">Enabled</span>
                        @elseif ($this->twoFactorEnabled)
                            <span
                                class="text-[10px] font-extrabold px-2 py-0.5 bg-amber-100 text-amber-700 border border-amber-200 tracking-wider uppercase">Pending</span>
                        @else
                            <span
                                class="text-[10px] font-extrabold px-2 py-0.5 bg-zinc-100 text-zinc-500 border border-zinc-200 tracking-wider uppercase">Disabled</span>
                        @endif
                    </div>
                </div>

                @if ($this->twoFactorEnabled)
                    {{-- QR code & secret (shown until confirmed, and on demand after) --}}
                    @if (!$this->twoFactorConfirmed)
                        <div class="bg-amber-50 border border-amber-200 p-4 mb-4">
                            <div class="text-[12px] text-amber-900 font-semibold mb-3">
                                {{ __('Scan this QR code in your authenticator app, then enter the generated code on your next sign-in to confirm setup.') }}
                            </div>
                            <div class="flex items-start gap-4 flex-wrap">
                                <div class="bg-white p-3 inline-block border border-zinc-200">
                                    {!! auth()->user()->twoFactorQrCodeSvg() !!}
                                </div>
                                <div class="text-[11px] text-zinc-700 font-mono break-all max-w-xs">
                                    <div class="font-bold uppercase tracking-wider text-zinc-500 mb-1">Setup Key</div>
                                    {{ decrypt(auth()->user()->two_factor_secret) }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mb-4">
                        <details class="group">
                            <summary
                                class="cursor-pointer text-[12px] font-bold text-zinc-700 hover:text-primary inline-flex items-center gap-1.5">
                                <flux:icon.chevron-right
                                    class="w-3.5 h-3.5 transition-transform group-open:rotate-90" />
                                Show recovery codes
                            </summary>
                            <div
                                class="mt-3 bg-zinc-50 border border-zinc-200 p-3 grid grid-cols-2 gap-2 font-mono text-[11px]">
                                @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                                    <div class="text-zinc-700">{{ $code }}</div>
                                @endforeach
                            </div>
                            <div class="text-[11px] text-zinc-500 mt-2">
                                {{ __('Each code can only be used once. Store them somewhere safe.') }}</div>
                        </details>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" wire:click="regenerateRecoveryCodes"
                            class="border-[1.5px] border-zinc-200 px-3.5 py-1.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:border-zinc-950 hover:bg-zinc-950 hover:text-white cursor-pointer">
                            Regenerate Recovery Codes
                        </button>
                        <button type="button" wire:click="disableTwoFactor"
                            wire:confirm="Disable two-factor authentication?"
                            class="border-[1.5px] border-red-500 text-red-500 px-3.5 py-1.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-red-500 hover:text-white cursor-pointer">
                            Disable
                        </button>
                    </div>
                @else
                    <button type="button" wire:click="enableTwoFactor"
                        class="border-[1.5px] border-zinc-950 px-3.5 py-1.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-zinc-950 hover:text-white cursor-pointer">
                        Enable
                    </button>
                @endif
            </div>
        </x-customer.settings-card>
    @endif

    {{-- Active Sessions --}}
    <x-customer.settings-card title="Active" titleEm="Sessions">
        <x-slot:icon>
            <flux:icon.computer-desktop />
        </x-slot:icon>

        {{-- Current session --}}
        <div class="flex items-center gap-3.5 px-5 py-3.5 border-b border-zinc-200">
            <div class="flex items-center justify-center w-[38px] h-[38px] bg-zinc-100 shrink-0">
                <flux:icon.computer-desktop class="w-[18px] h-[18px] text-zinc-500" />
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-bold text-zinc-950">{{ $this->currentSession['browser'] }} on
                    {{ $this->currentSession['platform'] }}</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">{{ $this->currentSession['ip'] ?: 'Unknown IP' }} ·
                    Active now</div>
            </div>
            <span
                class="text-[10px] font-extrabold px-2 py-0.5 bg-green-100 text-green-700 border border-green-200 tracking-wider uppercase shrink-0">This
                Device</span>
        </div>

        {{-- Sign out elsewhere --}}
        <div class="px-5 py-4 bg-zinc-50/60">
            <div class="text-[12px] text-zinc-600 mb-3">
                {{ __('Signing in on another device or browser creates a separate session. To revoke them, confirm your password below — you\'ll stay signed in here.') }}
            </div>
            <form wire:submit="logoutOtherDevices" class="flex items-stretch gap-2">
                <input type="password" wire:model="logout_password" placeholder="Confirm your password"
                    class="flex-1 border-[1.5px] border-zinc-200 px-3 py-2 text-[13px] font-medium outline-none transition-all focus:border-primary focus:ring-[3px] focus:ring-primary/8">
                <button type="submit"
                    class="border-[1.5px] border-red-500 text-red-500 px-3.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-red-500 hover:text-white cursor-pointer whitespace-nowrap">
                    Sign Out Everywhere Else
                </button>
            </form>
            @error('logout_password')
                <span class="text-[11px] text-red-500 font-semibold mt-1.5 block">{{ $message }}</span>
            @enderror
        </div>
    </x-customer.settings-card>
</div>
