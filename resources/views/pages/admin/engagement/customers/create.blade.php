<?php

use App\Livewire\Forms\Admin\CustomerForm;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Title, Computed};
use App\Models\{County, SubCounty};
use App\Enums\UserStatus;

new #[Title('Create Customer')] class extends Component {
    use WithFileUploads;

    public CustomerForm $form;

    public function save(): void
    {
        try {
            $this->form->store();
            $this->dispatch('notify', title: 'Customer Created', variant: 'success', message: 'Customer created successfully.');
            $this->redirectRoute('admin.customers.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Create Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function subCounties()
    {
        return $this->form->county_id ? SubCounty::where('county_id', $this->form->county_id)->orderBy('name')->get() : collect();
    }

    #[Computed]
    public function userStatus()
    {
        return UserStatus::cases();
    }
}; ?>

<div>
    @push('breadcrumbs')
    <flux:breadcrumbs><flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>
@endpush

    <flux:heading size="xl">Create Customer</flux:heading>
    <flux:subheading>Add a new customer account</flux:subheading>

    <form wire:submit="save" class="grid grid-cols-4 items-start gap-5 mt-6">
        @include('pages.admin.engagement.customers._form-fields')


        <div class="col-span-4">
            <flux:card class="bg-zinc-50 dark:bg-zinc-900 flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate class="cursor-pointer">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Create Customer
                </flux:button>
            </flux:card>
        </div>
    </form>
</div>
