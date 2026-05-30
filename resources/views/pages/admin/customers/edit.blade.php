<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Edit Customer — Admin')] class extends Component {
    #[Locked]
    public User $customer;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(User $customer): void
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->email = $customer->email;
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->customer->id)],
        ];

        if ($this->password !== '') {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        $this->validate($rules);

        $this->customer->name = $this->name;
        $this->customer->email = $this->email;

        if ($this->password !== '') {
            $this->customer->password = $this->password;
        }

        $this->customer->save();

        Flux::toast(heading: 'Customer updated', text: $this->customer->name.' has been saved.', variant: 'success');

        $this->redirectRoute('admin.customers.show', $this->customer, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.show', $customer)" wire:navigate>{{ $customer->name }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="lg" />
                <div>
                    <flux:heading size="xl">Edit customer</flux:heading>
                    <flux:subheading>{{ $customer->email }} · Joined {{ $customer->created_at->format('d F Y') }}</flux:subheading>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.show', $customer)" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
            </div>
        </div>

        <div class="mt-6 max-w-lg space-y-6">

            {{-- Profile --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Profile</flux:heading>
                </div>
                <div class="space-y-4 p-6">
                    <flux:input wire:model="name" label="Full name" required />
                    <flux:input wire:model="email" type="email" label="Email address" required />
                </div>
            </flux:card>

            {{-- Password --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Change password</flux:heading>
                </div>
                <div class="space-y-4 p-6">
                    <flux:input wire:model="password" type="password" label="New password" placeholder="Leave blank to keep current" />
                    <flux:input wire:model="password_confirmation" type="password" label="Confirm new password" placeholder="Repeat new password" />
                </div>
            </flux:card>

        </div>
    </form>
</div>
