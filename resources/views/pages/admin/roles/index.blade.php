<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Roles — Admin')] class extends Component {
    use WithPagination;

    private const PROTECTED_ROLES = PermissionSeeder::PROTECTED_ROLES;

    // --- Edit user modal state ---
    public bool $showUserModal = false;
    public ?int $editingUserId = null;
    public string $userName = '';
    public string $userEmail = '';
    public string $userPassword = '';
    public string $userRole = '';

    // --- User table filters ---
    #[Url(as: 'uq')]
    public string $userSearch = '';

    #[Url]
    public string $filterRole = '';

    #[Url]
    public string $filterStatus = '';

    public function updatedUserSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    // ==================================================
    // ROLES
    // ==================================================

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->with('users')
            ->orderBy('name')
            ->get();
    }

    public function delete(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            Flux::toast(heading: 'Cannot delete', text: 'The '.$role->name.' role is protected.', variant: 'danger');

            return;
        }

        if ($role->users_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $role->name.' is assigned to '.$role->users_count.' user(s).', variant: 'danger');

            return;
        }

        $role->delete();
        unset($this->roles);
        Flux::toast(heading: 'Role deleted', text: $role->name.' has been removed.', variant: 'success');
    }

    public function isProtected(string $name): bool
    {
        return in_array($name, self::PROTECTED_ROLES, true);
    }

    // ==================================================
    // USERS
    // ==================================================

    #[Computed]
    public function users()
    {
        return User::query()
            ->has('roles')
            ->with(['roles', 'addresses'])
            ->when($this->userSearch, fn ($q) => $q->where(function ($q) {
                $term = '%'.$this->userSearch.'%';
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($this->filterRole, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->filterRole)))
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->when($this->filterStatus === 'pending', fn ($q) => $q->whereNull('email_verified_at'))
            ->latest()
            ->paginate(10);
    }

    public function openCreateUser(): void
    {
        $this->reset(['editingUserId', 'userName', 'userEmail', 'userPassword', 'userRole']);
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function openEditUser(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editingUserId = $id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->userPassword = '';
        $this->userRole = $user->roles->first()?->name ?? '';
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $isCreating = $this->editingUserId === null;

        $this->validate([
            'userName' => ['required', 'string', 'max:255'],
            'userEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'userRole' => ['required', Rule::exists('roles', 'name')],
            ...($isCreating || $this->userPassword !== '' ? ['userPassword' => ['required', 'string', 'min:8']] : []),
        ]);

        $user = $isCreating ? new User : User::findOrFail($this->editingUserId);
        $user->name = $this->userName;
        $user->email = $this->userEmail;

        if ($this->userPassword !== '') {
            $user->password = $this->userPassword;
        }

        $user->save();
        $user->syncRoles([$this->userRole]);

        $this->showUserModal = false;
        unset($this->users, $this->roles);

        Flux::toast(
            heading: $isCreating ? 'User created' : 'User updated',
            text: $user->name.' has been saved.',
            variant: 'success',
        );
    }

    public function removeUser(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(heading: 'Cannot remove', text: 'You cannot revoke your own access.', variant: 'danger');

            return;
        }

        $user = User::findOrFail($id);
        $user->syncRoles([]);

        unset($this->users, $this->roles);
        Flux::toast(heading: 'Access revoked', text: $user->name."'s access has been revoked.", variant: 'success');
    }
}; ?>

@php
    $roleColor = fn (string $name) => match (true) {
        in_array($name, ['admin', 'super-admin'], true) => 'red',
        default => 'zinc',
    };
@endphp

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Roles</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Roles</flux:heading>
            <flux:subheading>Manage roles and user permissions.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="shield-check" :href="route('admin.roles.create')" wire:navigate>New role</flux:button>
    </div>

    {{-- Role cards --}}
    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->roles as $role)
            <flux:card :key="'role-'.$role->id" class="flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <flux:heading size="lg" class="capitalize">{{ str_replace('-', ' ', $role->name) }}</flux:heading>
                        @if ($first = $role->users->first())
                            <flux:avatar :name="$first->name" :initials="$first->initials()" size="sm" />
                        @endif
                    </div>
                    <div class="mt-4 space-y-1.5 text-sm text-zinc-500">
                        <div class="flex items-center gap-2">
                            <flux:icon.key variant="micro" class="size-4" />
                            {{ $role->permissions_count }} {{ Str::plural('Permission', $role->permissions_count) }}
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon.users variant="micro" class="size-4" />
                            {{ $role->users_count }} {{ Str::plural('User', $role->users_count) }}
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <a href="{{ route('admin.roles.edit', $role) }}" wire:navigate
                        class="text-sm font-medium text-brand-500 underline-offset-4 hover:underline">Edit Role</a>
                    @if ($this->isProtected($role->name))
                        <flux:icon.lock-closed variant="micro" class="size-4 text-zinc-400" />
                    @else
                        <flux:button size="xs" variant="ghost" icon="trash"
                            wire:click="delete({{ $role->id }})"
                            wire:confirm="Delete the '{{ addslashes($role->name) }}' role?"
                            class="text-red-500! hover:text-red-600!" />
                    @endif
                </div>
            </flux:card>
        @endforeach
    </div>

    {{-- Users --}}
    <flux:card class="mt-6 p-0 overflow-hidden">
        <div class="flex items-center justify-between gap-4 px-6 py-4">
            <flux:heading size="lg">Users</flux:heading>
            <flux:button size="sm" variant="ghost" icon="user-plus" wire:click="openCreateUser">Add user</flux:button>
        </div>

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-y border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="userSearch" placeholder="Search users…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All status</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="pending">Pending</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filterRole" class="w-40">
                    <flux:select.option value="">All roles</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ Str::headline($role->name) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>ID</flux:table.column>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    @php
                        $phone = $user->addresses->firstWhere('is_default', true)?->phone ?? $user->addresses->first()?->phone;
                        $roleName = $user->roles->first()?->name;
                    @endphp
                    <flux:table.row :key="'user-'.$user->id">
                        <flux:table.cell class="text-zinc-400">#{{ $user->id }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" />
                                <span class="font-medium dark:text-white">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <flux:badge size="sm" color="blue" inset="top bottom" class="ml-1">You</flux:badge>
                                    @endif
                                </span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500 tabular-nums">{{ $phone ?: '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($roleName)
                                <flux:badge size="sm" inset="top bottom" :color="$roleColor($roleName)">{{ Str::headline($roleName) }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$user->email_verified_at ? 'green' : 'amber'">
                                {{ $user->email_verified_at ? 'Active' : 'Pending' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($user->id === auth()->id())
                                {{-- Only one action available: edit. No dropdown needed. --}}
                                <flux:button size="xs" variant="ghost" icon="pencil-square"
                                    wire:click="openEditUser({{ $user->id }})" />
                            @else
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="openEditUser({{ $user->id }})">Edit</flux:menu.item>
                                        <flux:menu.item icon="user-minus" variant="danger"
                                            wire:click="removeUser({{ $user->id }})"
                                            wire:confirm="Revoke access for '{{ addslashes($user->name) }}'?">Revoke access</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">No users found.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
            @if ($this->users->hasPages())
                <flux:pagination :paginator="$this->users" />
            @else
                <flux:text size="sm">Showing {{ $this->users->count() }} {{ Str::plural('result', $this->users->count()) }}</flux:text>
            @endif
        </div>
    </flux:card>

    {{-- Create / edit user modal --}}
    <flux:modal wire:model.self="showUserModal" class="md:w-[480px]" :dismissible="false">
        <flux:heading>{{ $editingUserId ? 'Edit user' : 'Add user' }}</flux:heading>
        <flux:subheading>{{ $editingUserId ? "Update this user's details and role." : 'Create a user and assign a role.' }}</flux:subheading>

        <form wire:submit="saveUser" class="mt-5 space-y-4">
            <flux:input wire:model="userName" label="Name" placeholder="Jane Doe" required autofocus />
            <flux:input wire:model="userEmail" label="Email" type="email" placeholder="jane@example.com" required />

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="userRole" placeholder="Select a role…">
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ Str::headline($role->name) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:input wire:model="userPassword" label="{{ $editingUserId ? 'New password' : 'Password' }}" type="password" :placeholder="$editingUserId ? 'Leave blank to keep current' : 'Minimum 8 characters'" :required="! $editingUserId" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
