<?php

use App\Livewire\Forms\Admin\UserForm;
use App\Enums\UserStatus;
use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use Spatie\Permission\Models\Role;

new #[Title('Create Staff User')] class extends Component {
    public UserForm $form;

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
            $this->form->store();
            $this->dispatch('notify', title: 'User Created', variant: 'success', message: 'Staff user created successfully!');
            $this->redirectRoute('admin.access-control.roles.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notify', title: 'Validation Error', variant: 'warning', message: 'Please correct the highlighted fields.');
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to create staff user.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', title: 'Creation Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.access-control.roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create Staff User</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Create Staff User</flux:heading>
    <flux:subheading>Add a new staff member and assign their role</flux:subheading>

    <form wire:submit="save" class="space-y-5 mt-6">
        @include('pages.admin.access-control.users._form-fields')

        <flux:card class="flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-900">
            <flux:button variant="ghost" :href="route('admin.access-control.roles.index')" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                Create Staff User
            </flux:button>
        </flux:card>
    </form>
</div>
