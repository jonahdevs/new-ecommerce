<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Customer | Admin')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'password' => $this->password,
            'email_verified_at' => now(),
        ]);

        $this->redirectRoute('admin.customers.show', $customer, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New customer</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="create">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">New customer</flux:heading>
                <flux:subheading>Create a customer account.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="user-plus">Create customer</flux:button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm">Account details</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="name" label="Full name" placeholder="Jane Doe" required autofocus />
                        <flux:input wire:model="email" type="email" label="Email address" placeholder="jane@example.com" required />
                        <flux:field>
                            <flux:label>Phone number</flux:label>
                            <x-phone-input wire:model="phone" placeholder="700 000 000" />
                            <flux:error name="phone" />
                        </flux:field>
                    </div>
                </flux:card>
            </div>

            {{-- Side panel --}}
            <aside class="space-y-6">
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm">Password</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="password" type="password" label="Password" placeholder="Min. 8 characters" required />
                        <flux:input wire:model="password_confirmation" type="password" label="Confirm password" placeholder="Repeat password" required />
                    </div>
                </flux:card>
            </aside>

        </div>
    </form>
</div>
