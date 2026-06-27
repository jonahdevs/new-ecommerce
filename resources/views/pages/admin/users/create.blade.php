<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Add User | Admin')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = '';

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name'              => $this->name,
            'email'             => $this->email,
            'password'          => $this->password,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$this->role]);

        Flux::toast(heading: 'User created', text: $this->name.' has been added.', variant: 'success');

        $this->redirectRoute('admin.roles.index', navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Add user</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="create">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Add user</flux:heading>
                <flux:subheading>Create a new user account and assign a role.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.roles.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="user-plus">Add user</flux:button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Account details</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="name" label="Full name" placeholder="Jane Doe" required autofocus />
                        <flux:input wire:model="email" type="email" label="Email address" placeholder="jane@example.com" required />
                        <flux:select wire:model="role" label="Role" placeholder="Select a role…">
                            @foreach ($this->roles as $r)
                                <flux:select.option value="{{ $r->name }}">{{ str($r->name)->headline() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </flux:card>
            </div>

            {{-- Side panel --}}
            <aside class="space-y-6">
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Password</flux:heading>
                    </div>
                    <div class="p-6">
                        <flux:input wire:model="password" type="password" label="Password" placeholder="Min. 8 characters" required />
                    </div>
                </flux:card>
            </aside>

        </div>
    </form>
</div>
