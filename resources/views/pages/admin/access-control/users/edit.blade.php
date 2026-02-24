<?php

use App\Livewire\Forms\Admin\UserForm;
use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use Spatie\Permission\Models\Role;

new #[Title('Edit Staff User')] class extends Component {
    public UserForm $form;
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->form->setUser($user);
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    #[Computed]
    public function userStatus()
    {
        return UserStatus::cases();
    }

    public function save(): void
    {
        try {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', message: 'Staff user updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to update staff user.', [
                'user_id' => $this->user->id,
                'exception' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit {{ $user->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Edit {{ $user->name }}</flux:heading>
    <flux:subheading>Update staff member details and role</flux:subheading>

    <form wire:submit="save" class="space-y-5 mt-6">
        @include('pages.admin.access-control.users._form-fields')

        <flux:card class="flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-800">
            <flux:button variant="ghost" :href="route('admin.roles.index')" wire:navigate class="cursor-pointer">
                Cancel
            </flux:button>

            <flux:button type="submit" variant="primary" class="cursor-pointer">
                Update Staff User
            </flux:button>
        </flux:card>
    </form>
</div>
