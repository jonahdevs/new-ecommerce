<?php

use App\Models\User;
use App\Livewire\Forms\Admin\CustomerForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;

new #[Title('Create Customer')] class extends Component {
    use WithFileUploads;

    public CustomerForm $form;

    public function save(): void
    {
        try {
            $this->form->store();
            $this->dispatch('notify', variant: 'success', message: 'Customer created successfully.');
            $this->redirectRoute('admin.customers.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to create customer.', [
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
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Create Customer</flux:heading>
    <flux:subheading>Add a new customer account</flux:subheading>

    <form wire:submit="save" class="grid grid-cols-4 items-start gap-5 mt-6">
        @include('pages.admin.engagement.customers._form-fields')


        <div class="col-span-4">
            <flux:card class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Create Customer
                </flux:button>
            </flux:card>
        </div>
    </form>
</div>
