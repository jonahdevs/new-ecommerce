<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
/* @chisel-passkeys */
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
/* @end-chisel-passkeys */
/* @chisel-2fa */
use Livewire\Attributes\On;
/* @end-chisel-2fa */

new #[Layout('layouts::settings')] #[Title('Security')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $logout_password = '';

    public bool $embedded = false;

    /* @chisel-2fa */
    public bool $canManageTwoFactor;
    public bool $twoFactorEnabled;
    public bool $requiresConfirmation;
    /* @end-chisel-2fa */

    /* @chisel-passkeys */
    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';
    /* @end-chisel-passkeys */

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication, bool $embedded = false): void
    {
        $this->embedded = $embedded;

        /* @chisel-2fa */
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
        /* @end-chisel-2fa */

        /* @chisel-passkeys */
        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
        /* @end-chisel-passkeys */
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $e;
        }

        Auth::user()->update(['password' => $validated['password']]);
        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /** @return array{strength: int, label: string, color: string} */
    #[Computed]
    public function passwordStrength(): array
    {
        $score = 0;
        if (strlen($this->password) >= 8) {
            $score += 25;
        }
        if (preg_match('/[a-z]/', $this->password)) {
            $score += 25;
        }
        if (preg_match('/[A-Z]/', $this->password)) {
            $score += 25;
        }
        if (preg_match('/[0-9]/', $this->password) || preg_match('/[^A-Za-z0-9]/', $this->password)) {
            $score += 25;
        }

        [$label, $color] = match (true) {
            $score <= 25 => ['Weak', 'bg-red-500'],
            $score <= 50 => ['Fair', 'bg-amber-400'],
            $score <= 75 => ['Good', 'bg-blue-500'],
            default      => ['Strong', 'bg-emerald-500'],
        };

        return ['strength' => $score, 'label' => $label, 'color' => $color];
    }

    public function logoutOtherDevices(): void
    {
        $this->validate(
            ['logout_password' => ['required', 'string', 'current_password']],
            ['logout_password.current_password' => __('The password you entered is incorrect.')],
        );

        // Re-hashes the password in the DB so AuthenticateSession middleware
        // invalidates other sessions on their next request.
        Auth::logoutOtherDevices($this->logout_password);

        // Also delete the DB rows immediately so they're gone right now,
        // not only after the other device makes its next request.
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->reset('logout_password');
        unset($this->sessions);

        Flux::toast(variant: 'success', text: 'Signed out of all other devices.');
    }

    /* @chisel-passkeys */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);
        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);
        $deletePasskey(auth()->user(), $passkey);
        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }
    /* @end-chisel-passkeys */

    #[Computed]
    public function sessions(): \Illuminate\Support\Collection
    {
        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function (object $session) {
                $ua = (string) ($session->user_agent ?? '');

                return (object) [
                    'id'          => $session->id,
                    'ip'          => $session->ip_address ?? 'Unknown',
                    'browser'     => $this->parseBrowser($ua),
                    'platform'    => $this->parsePlatform($ua),
                    'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'is_current'  => $session->id === session()->getId(),
                ];
            });
    }

    /* @chisel-2fa */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());
        $this->twoFactorEnabled = false;
    }
    /* @end-chisel-2fa */

    private function parseBrowser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg/')                                        => 'Edge',
            str_contains($ua, 'Chrome') && ! str_contains($ua, 'Chromium')  => 'Chrome',
            str_contains($ua, 'Firefox')                                     => 'Firefox',
            str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')    => 'Safari',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')         => 'Opera',
            default                                                           => 'Browser',
        };
    }

    private function parsePlatform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Android')                                     => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')        => 'iOS',
            str_contains($ua, 'Windows')                                     => 'Windows',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux')                                       => 'Linux',
            default                                                           => 'Unknown OS',
        };
    }
}; ?>

@if (!$embedded)
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Password &amp; Security</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush
@endif

<div>
<section class="w-full space-y-4">
    @include('partials.settings-heading', ['embedded' => $embedded])

    {{-- ── Change Password ────────────────────────────────────────────── --}}
    <flux:card class="overflow-hidden p-0">
        <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3">
            <flux:icon.lock-closed variant="outline" class="size-4 text-zinc-600" />
            <flux:heading size="sm" class="uppercase tracking-wide">Change Password</flux:heading>
        </div>

        <form wire:submit="updatePassword" class="space-y-4 p-5">
            <flux:input wire:model="current_password"
                        :label="__('Current password')"
                        type="password"
                        required
                        autocomplete="current-password"
                        viewable />

            <div class="space-y-1.5">
                <flux:input wire:model.live="password"
                            :label="__('New password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            viewable />

                @if ($password)
                    @php $strength = $this->passwordStrength; @endphp
                    <div>
                        <div class="h-[3px] w-full overflow-hidden rounded-full bg-zinc-200">
                            <div class="h-full rounded-full transition-all duration-300 {{ $strength['color'] }}"
                                 style="width: {{ $strength['strength'] }}%"></div>
                        </div>
                        <p class="mt-1 text-[10px] font-bold uppercase tracking-wider
                            {{ match($strength['label']) {
                                'Weak'   => 'text-red-500',
                                'Fair'   => 'text-amber-500',
                                'Good'   => 'text-blue-500',
                                default  => 'text-emerald-600',
                            } }}">
                            {{ $strength['label'] }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="space-y-1">
                <flux:input wire:model="password_confirmation"
                            :label="__('Confirm new password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            viewable />
                @if ($password && $password_confirmation && $password !== $password_confirmation)
                    <p class="text-[11px] font-semibold text-red-500">Passwords do not match.</p>
                @endif
            </div>

            <div class="border-t border-zinc-100 pt-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">
                    {{ __('Update Password') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    {{-- ── Two-Factor Authentication ────────────────────────────────── --}}
    {{-- @chisel-2fa --}}
    @if ($canManageTwoFactor)
        <flux:card class="overflow-hidden p-0" wire:cloak>
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-5 py-3">
                <div class="flex items-center gap-3">
                    <flux:icon.shield-check variant="outline" class="size-4 text-zinc-600" />
                    <flux:heading size="sm" class="uppercase tracking-wide">Two-Factor Authentication</flux:heading>
                </div>
                @if ($twoFactorEnabled)
                    <flux:badge color="green" size="sm">Enabled</flux:badge>
                @else
                    <flux:badge color="zinc" size="sm">Disabled</flux:badge>
                @endif
            </div>

            <div class="p-5">
                <p class="text-[13px] text-ink-3">
                    Use Google Authenticator, Authy, or 1Password to generate one-time codes when signing in.
                </p>

                @if ($twoFactorEnabled)
                    <div class="mt-4 space-y-4">
                        <livewire:pages::account.settings.two-factor.recovery-codes :$requiresConfirmation />
                        <flux:button variant="danger" wire:click="disable"
                                     wire:confirm="Disable two-factor authentication?">
                            Disable 2FA
                        </flux:button>
                    </div>
                @else
                    <div class="mt-4">
                        <flux:modal.trigger name="two-factor-setup-modal">
                            <flux:button variant="primary" wire:click="$dispatch('start-two-factor-setup')">
                                Enable 2FA
                            </flux:button>
                        </flux:modal.trigger>
                        <livewire:pages::account.settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                    </div>
                @endif
            </div>
        </flux:card>
    @endif
    {{-- @end-chisel-2fa --}}

    {{-- ── Passkeys ─────────────────────────────────────────────────── --}}
    {{-- @chisel-passkeys --}}
    @if ($canManagePasskeys)
        <flux:card class="overflow-hidden p-0" wire:cloak>
            <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3">
                <flux:icon.key variant="outline" class="size-4 text-zinc-600" />
                <flux:heading size="sm" class="uppercase tracking-wide">Passkeys</flux:heading>
            </div>

            <div class="p-5">
                <p class="mb-4 text-[13px] text-ink-3">Sign in without a password using biometrics or a hardware key.</p>

                <div class="mb-4 overflow-hidden rounded-md border border-zinc-200">
                    @forelse ($passkeys as $passkey)
                        <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-zinc-100' : '' }}">
                            <div class="flex items-center gap-3">
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-zinc-100">
                                    <flux:icon.key class="size-4 text-zinc-500" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-[13px] font-medium text-ink">{{ $passkey['name'] }}</p>
                                        @if ($passkey['authenticator'])
                                            <flux:badge size="sm">{{ $passkey['authenticator'] }}</flux:badge>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-xs text-ink-3">
                                        Added {{ $passkey['created_at_diff'] }}
                                        @if ($passkey['last_used_at_diff'])
                                            · Last used {{ $passkey['last_used_at_diff'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <flux:button variant="ghost" size="sm" icon="trash-2" icon:variant="outline"
                                         wire:click="confirmDelete({{ $passkey['id'] }})"
                                         class="text-red-500 hover:bg-red-50 hover:text-red-600" />
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-md bg-zinc-100">
                                <flux:icon.key class="size-6 text-zinc-400" />
                            </div>
                            <p class="text-[13px] font-medium text-ink">No passkeys yet</p>
                            <p class="mt-0.5 text-xs text-ink-3">Add a passkey to sign in without a password</p>
                        </div>
                    @endforelse
                </div>

                <x-passkey-registration />
            </div>
        </flux:card>
    @endif
    {{-- @end-chisel-passkeys --}}

    {{-- ── Active Sessions ──────────────────────────────────────────── --}}
    <flux:card class="overflow-hidden p-0">
        <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-3">
            <flux:icon.computer-desktop variant="outline" class="size-4 text-zinc-600" />
            <flux:heading size="sm" class="uppercase tracking-wide">Active Sessions</flux:heading>
        </div>

        {{-- Session list --}}
        <div class="divide-y divide-zinc-100">
            @foreach ($this->sessions as $session)
                <div class="flex items-center gap-3.5 px-5 py-3.5">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-zinc-100">
                        @if (in_array($session->platform, ['Android', 'iOS']))
                            <flux:icon.device-phone-mobile class="size-4 text-zinc-500" />
                        @else
                            <flux:icon.computer-desktop class="size-4 text-zinc-500" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold text-ink">
                            {{ $session->browser }} on {{ $session->platform }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-ink-3">
                            {{ $session->ip }} · Active {{ $session->last_active }}
                        </p>
                    </div>
                    @if ($session->is_current)
                        <flux:badge color="zinc" size="sm" class="shrink-0">This device</flux:badge>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Sign out other devices --}}
        @if ($this->sessions->count() > 1)
            <div class="border-t border-zinc-200 bg-zinc-50/60 px-5 py-4">
                <p class="mb-3 text-[12px] text-ink-3">
                    To revoke all other sessions, confirm your password below — you'll stay signed in on this device.
                </p>
                <form wire:submit="logoutOtherDevices" class="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="flex-1">
                        <flux:input wire:model="logout_password"
                                    type="password"
                                    placeholder="Confirm your password"
                                    autocomplete="current-password"
                                    viewable />
                        <flux:error name="logout_password" class="mt-1" />
                    </div>
                    <flux:button type="submit" variant="danger" class="w-full shrink-0 sm:w-auto">
                        Sign Out Everywhere Else
                    </flux:button>
                </form>
            </div>
        @endif
    </flux:card>

</section>

{{-- @chisel-passkeys --}}
<flux:modal name="delete-passkey-modal" class="max-w-md md:min-w-md"
            @close="closeDeleteModal" wire:model="showDeleteModal">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Remove passkey') }}</flux:heading>
            <flux:text>
                {{ __('Are you sure you want to remove ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
            </flux:text>
        </div>
        <div class="flex justify-end gap-3">
            <flux:button variant="outline" wire:click="closeDeleteModal">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deletePasskey">Remove passkey</flux:button>
        </div>
    </div>
</flux:modal>
{{-- @end-chisel-passkeys --}}

</div>
