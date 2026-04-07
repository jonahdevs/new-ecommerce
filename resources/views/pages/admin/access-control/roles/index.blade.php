<?php

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\{Title, Computed};
use App\Models\User;
use App\Enums\UserStatus;
use App\Livewire\Forms\Admin\RoleForm;

new #[Title('Roles')] class extends Component {
    use WithPagination;

    public RoleForm $form;

    public string $search = '';
    public string $role = '';
    public string $status = '';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedRole(): void
    {
        $this->resetPage();
    }
    public function updatedStatus(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return Role::withCount(['permissions', 'users'])
            ->with(['users' => fn($q) => $q->take(4)])
            ->get();
    }

    #[Computed]
    public function userStatus()
    {
        return UserStatus::cases();
    }

    #[Computed]
    public function users()
    {
        return User::with('roles')
            ->staff()
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                }),
            )
            ->when($this->role, fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $this->role)))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate($this->perPage);
    }

    public function createRole(): void
    {
        try {
            $this->form->store();
            $this->dispatch('notify', title: 'Role Created', variant: 'success', message: 'Role created successfully.');
            $this->modal('create-role')->close();
            $this->form->reset();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to create role.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', title: 'Creation Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function deleteRole(Role $role): void
    {
        try {
            if ($role->is_system) {
                $this->dispatch('notify', title: 'Action Not Allowed', variant: 'warning', message: 'System roles cannot be deleted.');
                return;
            }

            // Move users to a default role before deleting
            $role->users()->each(fn($user) => $user->removeRole($role->name));
            $role->delete();

            $this->dispatch('notify', title: 'Role Deleted', variant: 'success', message: 'Role deleted successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', title: 'Deletion Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Roles</flux:breadcrumbs.item>
    </flux:breadcrumbs>


    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Roles</flux:heading>
            <flux:subheading>Manage roles and user permissions</flux:subheading>
        </div>

        <flux:button icon="shield-check" variant="primary" x-on:click="$flux.modal('create-role').show()"
            class="cursor-pointer">
            New Role
        </flux:button>
    </div>

    <div class="mt-6 space-y-8">

        {{-- ── Roles Grid ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($this->roles as $role)
                <flux:card class="space-y-3">

                    {{-- Header --}}
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <flux:heading size="lg">{{ str($role->name)->replace('_', ' ')->title() }}</flux:heading>

                        {{-- Stacked Avatars --}}
                        <flux:avatar.group>
                            @foreach ($role->users as $user)
                                <flux:tooltip :content="$user->name">
                                    @if ($user->avatar)
                                        <flux:avatar circle size="sm" src="{{ $user->avatar }}" />
                                    @else
                                        <flux:avatar circle size="sm" name="{{ $user->name }}" />
                                    @endif
                                </flux:tooltip>
                            @endforeach

                            @if ($role->users_count > 4)
                                <flux:avatar size="sm" circle> {{ $role->users_count - 4 }}+</flux:avatar>
                            @endif
                        </flux:avatar.group>
                    </div>

                    {{-- Details --}}
                    <div class="space-y-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                        <div class="flex items-center gap-2">
                            <flux:icon name="key" variant="outline" class="size-4" />
                            <span>{{ $role->permissions_count }} Permissions</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:icon name="users" variant="outline" class="size-4" />
                            <span>{{ $role->users_count }} Users</span>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-600">
                        <flux:link :href="route('admin.access-control.roles.edit', $role->id)" wire:navigate
                            class="text-sm">
                            Edit Role
                        </flux:link>

                        <div class="flex items-center gap-2">
                            @if (!$role->is_system)
                                <flux:button icon="trash" icon-variant="outline" variant="ghost" size="sm"
                                    tooltip="Delete Role" class="text-red-500! cursor-pointer"
                                    wire:click="deleteRole({{ $role->id }})"
                                    wire:confirm="Are you sure you want to delete this role?" />
                            @else
                                <flux:tooltip content="System role — cannot be deleted">
                                    <flux:icon name="lock-closed" class="size-4 text-zinc-400" />
                                </flux:tooltip>
                            @endif

                        </div>
                    </div>

                </flux:card>
            @endforeach
        </div>

        {{-- ── Users Table ── --}}
        <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200">
                <flux:button icon="plus-circle" variant="primary" size="sm" class="cursor-pointer"
                    :href="route('admin.access-control.users.create')" wire:navigate>
                    Add User
                </flux:button>
            </div>

            {{-- Filters --}}
            <div class="flex items-center flex-wrap gap-3 px-5 py-3 border-b dark:border-zinc-600 border-zinc-200 dark:border-zinc-600">
                <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                    placeholder="Search users..." class="max-w-xs" />

                <div class="ms-auto flex items-center gap-3 flex-wrap">
                    <flux:select wire:model.live="status" class="w-36">
                        <flux:select.option value="">All Status</flux:select.option>
                        @foreach ($this->userStatus as $status)
                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="role" class="w-36">
                        <flux:select.option value="">All Roles</flux:select.option>
                        @foreach ($this->roles as $r)
                            <flux:select.option :value="$r->name" class="capitalize">
                                {{ str($r->name)->replace('_', ' ')->title() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            {{-- Table --}}
            <flux:table :paginate="$this->users">
                <flux:table.columns>
                    <flux:table.column class="ps-5!">ID</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Phone</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->users as $user)
                        <flux:table.row :key="$user->id">

                            <flux:table.cell class="ps-5! text-zinc-400 text-sm">
                                #{{ $user->id }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($user->avatar)
                                        <flux:avatar circle size="sm" src="{{ $user->avatar }}" />
                                    @else
                                        <flux:avatar circle size="sm" name="{{ $user->name }}" />
                                    @endif

                                    <flux:heading size="sm">{{ $user->name }}</flux:heading>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:subheading>{{ $user->email }}</flux:subheading>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:subheading>{{ $user->phone_number ?? '—' }}</flux:subheading>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-1 flex-wrap">
                                    @foreach ($user->roles as $r)
                                        @php
                                            $roleColor = match ($r->name) {
                                                'admin' => 'red',
                                                'manager' => 'blue',
                                                default => 'zinc',
                                            };
                                        @endphp
                                        <flux:badge size="sm" :color="$roleColor" variant="outline"
                                            class="capitalize">
                                            {{ str($r->name)->replace('_', ' ')->title() }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $statusColor = match ($user->status) {
                                        'active' => 'green',
                                        'banned' => 'red',
                                        default => 'yellow',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$statusColor" variant="soft" class="capitalize">
                                    {{ $user->status }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="pe-4! flex items-center justify-end gap-1">
                                <flux:button icon="pencil-square" icon-variant="outline" variant="ghost" size="sm" tooltip="Edit"
                                    :href="route('admin.access-control.users.edit', $user)" class="cursor-pointer"
                                    wire:navigate />

                                <flux:button icon="trash" icon-variant="outline" variant="ghost" tooltip="Delete"
                                    size="sm" class="text-red-500! cursor-pointer" />
                            </flux:table.cell>
                        </flux:table.row>

                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center gap-3 text-zinc-400">
                                    <flux:icon.users class="w-10 h-10 opacity-40" />
                                    <div>
                                        <flux:heading size="sm">No users found</flux:heading>
                                        <flux:subheading class="mt-0.5">
                                            @if ($this->search || $this->role || $this->status)
                                                No results match your current filters.
                                            @else
                                                Staff users will appear here once they are added.
                                            @endif
                                        </flux:subheading>
                                    </div>
                                    @if ($this->search || $this->role || $this->status)
                                        <flux:button variant="ghost" size="sm"
                                            wire:click="$set('search', ''); $set('role', ''); $set('status', '')">
                                            Clear filters
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <flux:modal name="create-role" class="md:w-96">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Create New Role</flux:heading>
                <flux:subheading>
                    Use lowercase letters and underscores only.
                    e.g. <code
                        class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">logistics_manager</code>
                </flux:subheading>
            </div>

            <form wire:submit="createRole" class="space-y-4">
                <flux:field>
                    <flux:label>Role Name</flux:label>
                    <flux:input wire:model="form.name" placeholder="e.g. logistics_manager" autofocus />
                    <flux:description>
                        This will display as "{{ str($form->name ?: 'role name')->replace('_', ' ')->title() }}"
                    </flux:description>
                    <flux:error name="form.name" />
                </flux:field>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="ghost" x-on:click="$flux.modal('create-role').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Create Role
                    </flux:button>
                </div>
            </form>

        </div>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
