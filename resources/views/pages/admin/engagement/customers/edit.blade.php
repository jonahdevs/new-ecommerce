<?php

use App\Models\{User, County, Area};
use App\Livewire\Forms\Admin\CustomerForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Title, Computed};
use App\Enums\UserStatus;

new #[Title('Edit Customer')] class extends Component {
    use WithFileUploads;

    public CustomerForm $form;
    public User $customer;

    public function mount(User $customer): void
    {
        $this->customer = $customer;
        $this->form->setCustomer($customer);
    }

    public function save(): void
    {
        try {
            $this->form->update();
            $this->dispatch('notify', title: 'Customer Updated', variant: 'success', message: 'Customer updated successfully.');
            $this->redirectRoute('admin.customers.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            $this->customer->delete();
            $this->dispatch('notify', title: 'Customer Deleted', variant: 'success', message: 'Customer deleted successfully.');
            $this->redirectRoute('admin.customers.index', navigate: true);
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function areas()
    {
        // Reactively filters when county_id changes
        return $this->form->county_id ? Area::where('county_id', $this->form->county_id)->orderBy('name')->get() : collect();
    }

    #[Computed]
    public function userStatus()
    {
        return UserStatus::cases();
    }

    public function sendPasswordReset(): void
    {
        try {
            $this->form->sendPasswordResetLink();
            $this->dispatch('notify', title: 'Reset Link Sent', variant: 'success', message: 'Password reset link sent.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Send Failed', variant: 'danger', message: 'Failed to send reset link.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Edit Customer</flux:heading>
    <flux:subheading>{{ $customer->name }} &mdash; {{ $customer->email }}</flux:subheading>

    <form wire:submit="save" class="grid grid-cols-4 items-start gap-5 mt-6">
        @include('pages.admin.engagement.customers._form-fields', ['customer' => $customer])

        <div class="col-span-4">
            <flux:card class="bg-zinc-50 dark:bg-zinc-900 flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate class="cursor-pointer">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Save Changes
                </flux:button>
            </flux:card>
        </div>
    </form>
</div>
