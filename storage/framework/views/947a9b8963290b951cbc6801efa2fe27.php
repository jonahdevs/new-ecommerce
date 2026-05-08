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

<?php
    $inputClass =
        'w-full border-[1.5px] border-zinc-200 px-3 py-2.5 pr-10 text-[13px] font-medium outline-none transition-all focus:border-primary focus:ring-[3px] focus:ring-primary/8';
    $labelClass = 'block text-[10px] font-bold tracking-[0.1em] uppercase text-zinc-500 mb-1.5';
    $eyeBtnClass =
        'absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer';
?>

<div class="flex flex-col gap-5">
    
    <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Change','titleEm' => 'Password']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Change','titleEm' => 'Password']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal7649f9fde3f65e39f506d39dd1ac88cb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7649f9fde3f65e39f506d39dd1ac88cb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.lock-closed','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.lock-closed'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7649f9fde3f65e39f506d39dd1ac88cb)): ?>
<?php $attributes = $__attributesOriginal7649f9fde3f65e39f506d39dd1ac88cb; ?>
<?php unset($__attributesOriginal7649f9fde3f65e39f506d39dd1ac88cb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7649f9fde3f65e39f506d39dd1ac88cb)): ?>
<?php $component = $__componentOriginal7649f9fde3f65e39f506d39dd1ac88cb; ?>
<?php unset($__componentOriginal7649f9fde3f65e39f506d39dd1ac88cb); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>

        <form wire:submit="updatePassword" class="px-5 py-5">
            <div class="mb-3.5">
                <label class="<?php echo e($labelClass); ?>">Current Password *</label>
                <div class="relative">
                    <input :type="$wire.showCurrentPassword ? 'text' : 'password'" wire:model="current_password"
                        placeholder="Enter current password" class="<?php echo e($inputClass); ?>" required>
                    <button type="button" @click="$wire.showCurrentPassword = !$wire.showCurrentPassword"
                        class="<?php echo e($eyeBtnClass); ?>">
                        <?php if (isset($component)) { $__componentOriginal2e57535a42d25d5415c31aa83132341b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e57535a42d25d5415c31aa83132341b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.eye','data' => ['class' => 'w-4 h-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.eye'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e57535a42d25d5415c31aa83132341b)): ?>
<?php $attributes = $__attributesOriginal2e57535a42d25d5415c31aa83132341b; ?>
<?php unset($__attributesOriginal2e57535a42d25d5415c31aa83132341b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e57535a42d25d5415c31aa83132341b)): ?>
<?php $component = $__componentOriginal2e57535a42d25d5415c31aa83132341b; ?>
<?php unset($__componentOriginal2e57535a42d25d5415c31aa83132341b); ?>
<?php endif; ?>
                    </button>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="mb-3.5">
                <label class="<?php echo e($labelClass); ?>">New Password *</label>
                <div class="relative">
                    <input :type="$wire.showNewPassword ? 'text' : 'password'" wire:model.live="password"
                        placeholder="Min. 8 characters" class="<?php echo e($inputClass); ?>" required>
                    <button type="button" @click="$wire.showNewPassword = !$wire.showNewPassword"
                        class="<?php echo e($eyeBtnClass); ?>">
                        <?php if (isset($component)) { $__componentOriginal2e57535a42d25d5415c31aa83132341b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e57535a42d25d5415c31aa83132341b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.eye','data' => ['class' => 'w-4 h-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.eye'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e57535a42d25d5415c31aa83132341b)): ?>
<?php $attributes = $__attributesOriginal2e57535a42d25d5415c31aa83132341b; ?>
<?php unset($__attributesOriginal2e57535a42d25d5415c31aa83132341b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e57535a42d25d5415c31aa83132341b)): ?>
<?php $component = $__componentOriginal2e57535a42d25d5415c31aa83132341b; ?>
<?php unset($__componentOriginal2e57535a42d25d5415c31aa83132341b); ?>
<?php endif; ?>
                    </button>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($password): ?>
                    <?php $strength = $this->getPasswordStrength(); ?>
                    <div class="mt-2">
                        <div class="h-[3px] bg-zinc-200 rounded-full overflow-hidden mb-1">
                            <div class="h-full rounded-full transition-all duration-300"
                                style="width: <?php echo e($strength['strength']); ?>%; background-color: <?php echo e($strength['color']); ?>">
                            </div>
                        </div>
                        <div class="text-[10px] font-bold tracking-wider uppercase"
                            style="color: <?php echo e($strength['color']); ?>">
                            <?php echo e($strength['label']); ?>

                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div>
                <label class="<?php echo e($labelClass); ?>">Confirm New Password *</label>
                <div class="relative">
                    <input :type="$wire.showConfirmPassword ? 'text' : 'password'" wire:model="password_confirmation"
                        placeholder="Repeat new password" class="<?php echo e($inputClass); ?>" required>
                    <button type="button" @click="$wire.showConfirmPassword = !$wire.showConfirmPassword"
                        class="<?php echo e($eyeBtnClass); ?>">
                        <?php if (isset($component)) { $__componentOriginal2e57535a42d25d5415c31aa83132341b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2e57535a42d25d5415c31aa83132341b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.eye','data' => ['class' => 'w-4 h-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.eye'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-4 h-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2e57535a42d25d5415c31aa83132341b)): ?>
<?php $attributes = $__attributesOriginal2e57535a42d25d5415c31aa83132341b; ?>
<?php unset($__attributesOriginal2e57535a42d25d5415c31aa83132341b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2e57535a42d25d5415c31aa83132341b)): ?>
<?php $component = $__componentOriginal2e57535a42d25d5415c31aa83132341b; ?>
<?php unset($__componentOriginal2e57535a42d25d5415c31aa83132341b); ?>
<?php endif; ?>
                    </button>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($password && $password_confirmation && $password !== $password_confirmation): ?>
                    <span class="text-[11px] text-red-500 font-semibold mt-1 block">Passwords do not match.</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="flex items-center gap-2.5 mt-5 pt-4 border-t border-zinc-200">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-primary text-white px-6 py-2.5 font-serif text-[13px] font-extrabold tracking-wider uppercase transition-colors hover:bg-[#e03d00] cursor-pointer">
                    <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                    <span wire:loading wire:target="updatePassword">Updating...</span>
                </button>
            </div>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $attributes = $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $component = $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Features::canManageTwoFactorAuthentication()): ?>
        <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Two-Factor','titleEm' => 'Authentication']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Two-Factor','titleEm' => 'Authentication']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php $__env->slot('icon', null, []); ?> 
                <?php if (isset($component)) { $__componentOriginalf870514c33bb1b53395ba02235f60146 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf870514c33bb1b53395ba02235f60146 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.shield-check','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.shield-check'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf870514c33bb1b53395ba02235f60146)): ?>
<?php $attributes = $__attributesOriginalf870514c33bb1b53395ba02235f60146; ?>
<?php unset($__attributesOriginalf870514c33bb1b53395ba02235f60146); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf870514c33bb1b53395ba02235f60146)): ?>
<?php $component = $__componentOriginalf870514c33bb1b53395ba02235f60146; ?>
<?php unset($__componentOriginalf870514c33bb1b53395ba02235f60146); ?>
<?php endif; ?>
             <?php $__env->endSlot(); ?>

            <div class="px-5 py-5">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <div class="text-[13px] font-bold text-zinc-950 mb-0.5">Authenticator App</div>
                        <div class="text-[12px] text-zinc-500">Use Google Authenticator, Authy, or 1Password to generate
                            one-time codes when signing in.</div>
                    </div>
                    <div class="shrink-0">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->twoFactorConfirmed): ?>
                            <span
                                class="text-[10px] font-extrabold px-2 py-0.5 bg-green-100 text-green-700 border border-green-200 tracking-wider uppercase">Enabled</span>
                        <?php elseif($this->twoFactorEnabled): ?>
                            <span
                                class="text-[10px] font-extrabold px-2 py-0.5 bg-amber-100 text-amber-700 border border-amber-200 tracking-wider uppercase">Pending</span>
                        <?php else: ?>
                            <span
                                class="text-[10px] font-extrabold px-2 py-0.5 bg-zinc-100 text-zinc-500 border border-zinc-200 tracking-wider uppercase">Disabled</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->twoFactorEnabled): ?>
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$this->twoFactorConfirmed): ?>
                        <div class="bg-amber-50 border border-amber-200 p-4 mb-4">
                            <div class="text-[12px] text-amber-900 font-semibold mb-3">
                                <?php echo e(__('Scan this QR code in your authenticator app, then enter the generated code on your next sign-in to confirm setup.')); ?>

                            </div>
                            <div class="flex items-start gap-4 flex-wrap">
                                <div class="bg-white p-3 inline-block border border-zinc-200">
                                    <?php echo auth()->user()->twoFactorQrCodeSvg(); ?>

                                </div>
                                <div class="text-[11px] text-zinc-700 font-mono break-all max-w-xs">
                                    <div class="font-bold uppercase tracking-wider text-zinc-500 mb-1">Setup Key</div>
                                    <?php echo e(decrypt(auth()->user()->two_factor_secret)); ?>

                                </div>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="mb-4">
                        <details class="group">
                            <summary
                                class="cursor-pointer text-[12px] font-bold text-zinc-700 hover:text-primary inline-flex items-center gap-1.5">
                                <?php if (isset($component)) { $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.chevron-right','data' => ['class' => 'w-3.5 h-3.5 transition-transform group-open:rotate-90']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-3.5 h-3.5 transition-transform group-open:rotate-90']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $attributes = $__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__attributesOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3)): ?>
<?php $component = $__componentOriginal31cb76c8d087d4f00797aeea7232b4c3; ?>
<?php unset($__componentOriginal31cb76c8d087d4f00797aeea7232b4c3); ?>
<?php endif; ?>
                                Show recovery codes
                            </summary>
                            <div
                                class="mt-3 bg-zinc-50 border border-zinc-200 p-3 grid grid-cols-2 gap-2 font-mono text-[11px]">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="text-zinc-700"><?php echo e($code); ?></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                            <div class="text-[11px] text-zinc-500 mt-2">
                                <?php echo e(__('Each code can only be used once. Store them somewhere safe.')); ?></div>
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
                <?php else: ?>
                    <button type="button" wire:click="enableTwoFactor"
                        class="border-[1.5px] border-zinc-950 px-3.5 py-1.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-zinc-950 hover:text-white cursor-pointer">
                        Enable
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $attributes = $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $component = $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if (isset($component)) { $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.customer.settings-card','data' => ['title' => 'Active','titleEm' => 'Sessions']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('customer.settings-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Active','titleEm' => 'Sessions']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('icon', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal7906b2bbeb0e6efeed16189cac9d419a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.computer-desktop','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.computer-desktop'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a)): ?>
<?php $attributes = $__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a; ?>
<?php unset($__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7906b2bbeb0e6efeed16189cac9d419a)): ?>
<?php $component = $__componentOriginal7906b2bbeb0e6efeed16189cac9d419a; ?>
<?php unset($__componentOriginal7906b2bbeb0e6efeed16189cac9d419a); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>

        
        <div class="flex items-center gap-3.5 px-5 py-3.5 border-b border-zinc-200">
            <div class="flex items-center justify-center w-[38px] h-[38px] bg-zinc-100 shrink-0">
                <?php if (isset($component)) { $__componentOriginal7906b2bbeb0e6efeed16189cac9d419a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::icon.computer-desktop','data' => ['class' => 'w-[18px] h-[18px] text-zinc-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::icon.computer-desktop'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-[18px] h-[18px] text-zinc-500']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a)): ?>
<?php $attributes = $__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a; ?>
<?php unset($__attributesOriginal7906b2bbeb0e6efeed16189cac9d419a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7906b2bbeb0e6efeed16189cac9d419a)): ?>
<?php $component = $__componentOriginal7906b2bbeb0e6efeed16189cac9d419a; ?>
<?php unset($__componentOriginal7906b2bbeb0e6efeed16189cac9d419a); ?>
<?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-bold text-zinc-950"><?php echo e($this->currentSession['browser']); ?> on
                    <?php echo e($this->currentSession['platform']); ?></div>
                <div class="text-[11px] text-zinc-500 mt-0.5"><?php echo e($this->currentSession['ip'] ?: 'Unknown IP'); ?> ·
                    Active now</div>
            </div>
            <span
                class="text-[10px] font-extrabold px-2 py-0.5 bg-green-100 text-green-700 border border-green-200 tracking-wider uppercase shrink-0">This
                Device</span>
        </div>

        
        <div class="px-5 py-4 bg-zinc-50/60">
            <div class="text-[12px] text-zinc-600 mb-3">
                <?php echo e(__('Signing in on another device or browser creates a separate session. To revoke them, confirm your password below — you\'ll stay signed in here.')); ?>

            </div>
            <form wire:submit="logoutOtherDevices" class="flex items-stretch gap-2">
                <input type="password" wire:model="logout_password" placeholder="Confirm your password"
                    class="flex-1 border-[1.5px] border-zinc-200 px-3 py-2 text-[13px] font-medium outline-none transition-all focus:border-primary focus:ring-[3px] focus:ring-primary/8">
                <button type="submit"
                    class="border-[1.5px] border-red-500 text-red-500 px-3.5 font-serif text-[11px] font-extrabold tracking-wider uppercase transition-all hover:bg-red-500 hover:text-white cursor-pointer whitespace-nowrap">
                    Sign Out Everywhere Else
                </button>
            </form>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['logout_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="text-[11px] text-red-500 font-semibold mt-1.5 block"><?php echo e($message); ?></span>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $attributes = $__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__attributesOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb)): ?>
<?php $component = $__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb; ?>
<?php unset($__componentOriginal0c8ec7f6f390e4e14bf1dbac51ec15eb); ?>
<?php endif; ?>
</div>
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pages\customer\settings\security.blade.php ENDPATH**/ ?>