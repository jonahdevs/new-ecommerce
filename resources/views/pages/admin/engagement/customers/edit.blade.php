<?php

use App\Models\User;
use App\Livewire\Forms\Admin\CustomerForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;

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
            $this->dispatch('notify', variant: 'success', message: 'Customer updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to update customer.', [
                'customer_id' => $this->customer->id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            $this->customer->delete();
            $this->dispatch('notify', variant: 'success', message: 'Customer deleted successfully.');
            $this->redirectRoute('admin.customers.index', navigate: true);
        } catch (\Throwable $e) {
            logger()->error('Failed to delete customer.', [
                'customer_id' => $this->customer->id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Edit Customer</flux:heading>
    <flux:subheading>{{ $customer->name }} &mdash; {{ $customer->email }}</flux:subheading>

    <form wire:submit="save" class="grid grid-cols-4 items-start gap-5 mt-6">
        @include('pages.admin.engagement.customers._form-fields', ['customer' => $customer])

        <div class="col-span-4">
            <flux:card class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </flux:card>
        </div>
    </form>
</div>
