<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Add Staff | Admin')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = '';

    public function mount(): void
    {
        $this->role = $this->roles->first()?->name ?? '';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'password' => $this->password,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($this->role);

        Flux::toast(heading: 'Staff added', text: $this->name.' has been added as '.str($this->role)->headline().'.', variant: 'success');

        $this->redirectRoute('admin.staff.index', navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.staff.index')" wire:navigate>Staff</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Add staff</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="create">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Add staff member</flux:heading>
                <flux:subheading>Create a new staff account and assign a role.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.staff.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="user-plus">Add staff member</flux:button>
            </div>
        </div>

        <div class="mt-6 max-w-lg space-y-6">

            {{-- Account --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm">Account details</flux:heading>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="name" label="Full name" placeholder="Jane Doe" required autofocus />
                        <flux:input wire:model="email" type="email" label="Email address" placeholder="jane@example.com" required />
                    </div>
                    <flux:field>
                        <flux:label>Phone number</flux:label>
                        <x-phone-input wire:model="phone" placeholder="700 000 000" />
                        <flux:description>Used for WhatsApp staff notifications.</flux:description>
                        <flux:error name="phone" />
                    </flux:field>
                    <flux:select wire:model="role" label="Role">
                        @foreach ($this->roles as $r)
                            <flux:select.option value="{{ $r->name }}">{{ str($r->name)->headline() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </flux:card>

            {{-- Password --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:heading size="sm">Password</flux:heading>
                </div>
                <div class="space-y-4 p-6">
                    <flux:input wire:model="password" type="password" label="Password" placeholder="Min. 8 characters" required />
                    <flux:input wire:model="password_confirmation" type="password" label="Confirm password" placeholder="Repeat password" required />
                </div>
            </flux:card>

        </div>
    </form>
</div>
