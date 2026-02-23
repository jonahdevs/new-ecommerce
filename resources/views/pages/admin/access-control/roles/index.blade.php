<?php

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\{Title, Computed};
use App\Models\User;

new #[Title('Roles')] class extends Component {
    use WithPagination;

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
    public function users()
    {
        return User::with('roles')
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
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Roles</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Roles</flux:heading>
    <flux:subheading>Manage roles and user permissions</flux:subheading>

    <div class="mt-6 space-y-8">

        {{-- ── Roles Grid ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($this->roles as $role)
                <flux:card class="space-y-3">

                    {{-- Header --}}
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <flux:heading size="lg" class="capitalize">{{ $role->name }}</flux:heading>

                        {{-- Stacked Avatars --}}
                        <div class="flex items-center -space-x-2">
                            @foreach ($role->users as $user)
                                <flux:tooltip :content="$user->name">
                                    <div
                                        class="size-9 rounded-full border-2 border-white dark:border-zinc-800 bg-zinc-200 dark:bg-zinc-700 overflow-hidden z-10 hover:z-20 hover:scale-105 transition-transform duration-200 shrink-0">
                                        @if ($user->avatar)
                                            <img src="{{ asset('storage/' . $user->avatar) }}"
                                                class="w-full h-full object-cover" alt="{{ $user->name }}" />
                                        @else
                                            <div
                                                class="w-full h-full grid place-items-center text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </flux:tooltip>
                            @endforeach

                            @if ($role->users_count > 4)
                                <div
                                    class="size-9 rounded-full border-2 border-white dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-700 grid place-items-center text-xs font-semibold text-zinc-500 z-10">
                                    +{{ $role->users_count - 4 }}
                                </div>
                            @endif
                        </div>
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
                    <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-700">
                        <flux:link :href="route('admin.roles.edit', $role->id)" wire:navigate class="text-sm">
                            Edit Role
                        </flux:link>
                        <flux:tooltip content="Duplicate role">
                            <flux:button icon="document-duplicate" icon-variant="outline" variant="ghost"
                                size="sm" />
                        </flux:tooltip>
                    </div>

                </flux:card>
            @endforeach
        </div>

        {{-- ── Users Table ── --}}
        <flux:card class="p-0">

            {{-- Filters --}}
            <div class="flex items-center flex-wrap gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                    placeholder="Search users..." class="max-w-xs" />

                <div class="ms-auto flex items-center gap-3 flex-wrap">
                    <flux:select wire:model.live="status" placeholder="All Status" class="w-36">
                        <flux:select.option value="">All Status</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                        <flux:select.option value="banned">Banned</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="role" placeholder="All Roles" class="w-36">
                        <flux:select.option value="">All Roles</flux:select.option>
                        @foreach ($this->roles as $r)
                            <flux:select.option :value="$r->name" class="capitalize">{{ $r->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button icon="plus-circle" variant="primary" size="sm">
                        Add User
                    </flux:button>
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
                                    <div
                                        class="size-8 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden shrink-0">
                                        @if ($user->avatar)
                                            <img src="{{ asset('storage/' . $user->avatar) }}"
                                                class="w-full h-full object-cover" alt="{{ $user->name }}" />
                                        @else
                                            <div
                                                class="w-full h-full grid place-items-center text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <flux:text class="text-sm font-medium">{{ $user->name }}</flux:text>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:link href="mailto:{{ $user->email }}" class="text-sm">
                                    {{ $user->email }}
                                </flux:link>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $user->phone_number ?? '—' }}</flux:text>
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
                                            {{ $r->name }}
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

                            <flux:table.cell class="pe-4!">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:tooltip content="Edit">
                                        <flux:button icon="pencil-square" variant="ghost" size="sm" />
                                    </flux:tooltip>
                                    <flux:tooltip content="Archive">
                                        <flux:button icon="trash" icon-variant="outline" variant="ghost"
                                            size="sm" class="text-red-500!" />
                                    </flux:tooltip>
                                </div>
                            </flux:table.cell>

                        </flux:table.row>

                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon name="users" class="size-10 text-zinc-300" />
                                    <flux:heading size="lg" class="text-zinc-600">No Users Found</flux:heading>
                                    <flux:text class="text-sm text-zinc-400">
                                        {{ $this->search ? 'No users match your search.' : 'No users have been added yet.' }}
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            {{-- Footer --}}
            <div
                class="flex items-center justify-between flex-wrap gap-3 px-5 py-3 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-400">
                    Showing {{ $this->users->firstItem() ?? 0 }}–{{ $this->users->lastItem() ?? 0 }}
                    of {{ $this->users->total() }} results
                </flux:text>

                <div class="flex items-center gap-2">
                    <flux:text class="text-sm shrink-0">Rows per page</flux:text>
                    <flux:select wire:model.live="perPage" class="w-20">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                        <flux:select.option value="100">100</flux:select.option>
                    </flux:select>
                </div>
            </div>

        </flux:card>
    </div>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
