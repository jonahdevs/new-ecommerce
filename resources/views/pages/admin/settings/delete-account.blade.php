<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use PasswordValidationRules;

    public string $delete_password = '';

    /**
     * The sole remaining super-admin must not delete themselves, or no one
     * could manage roles and permissions afterwards.
     */
    #[Computed]
    public function isLastSuperAdmin(): bool
    {
        $user = Auth::user();

        if (! $user->hasRole('super-admin')) {
            return false;
        }

        return Role::findByName('super-admin')->users()->count() <= 1;
    }

    public function deleteAccount(Logout $logout): void
    {
        if ($this->isLastSuperAdmin()) {
            $this->addError('delete_password', 'You are the only super-admin. Assign the super-admin role to another account before deleting yours.');

            return;
        }

        $this->validate(['delete_password' => $this->currentPasswordRules()]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:card class="overflow-hidden p-0 border-red-200">
    <div class="flex items-center gap-3 border-b border-red-100 bg-red-50 px-5 py-3 dark:border-red-900/40 dark:bg-red-950/20">
        <flux:icon.exclamation-triangle variant="outline" class="size-4 text-red-500" />
        <flux:heading size="sm" class="uppercase tracking-wide text-red-700!">Delete account</flux:heading>
    </div>

    <div class="p-5">
        @if ($this->isLastSuperAdmin)
            <p class="text-[13px] text-zinc-500 dark:text-zinc-400">
                You are the only <span class="font-semibold">super-admin</span>. Assign the super-admin role to
                another staff member from
                <a href="{{ route('admin.staff.index') }}" wire:navigate class="text-brand-500 hover:underline">Staff</a>
                before you can delete your own account.
            </p>
        @else
            <p class="mb-4 text-[13px] text-zinc-500 dark:text-zinc-400">
                {{ __('Deleting your account signs you out and permanently removes your staff access. This cannot be undone.') }}
            </p>
            <flux:modal.trigger name="confirm-staff-deletion">
                <flux:button variant="danger" data-test="delete-user-button">Delete my account</flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    <flux:modal name="confirm-staff-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form wire:submit="deleteAccount" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Once your account is deleted, your staff access is permanently removed. Enter your password to confirm.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="delete_password" :label="__('Password')" type="password" viewable />
            <flux:error name="delete_password" />

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</flux:card>
