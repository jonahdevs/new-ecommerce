<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Title('Permissions')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $role = '';
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedRole(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::with('roles')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->when($this->role, fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $this->role)))->latest()->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Permissions</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Permissions</flux:heading>
    <flux:subheading>View and filter all permissions across roles</flux:subheading>

    <div class="mt-6">
        <flux:card class="p-0">

            {{-- Filters --}}
            <div class="flex items-center flex-wrap gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                    placeholder="Search permissions..." class="max-w-xs" />

                <div class="ms-auto">
                    <flux:select wire:model.live="role" placeholder="All Roles" class="w-36">
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
            <flux:table :paginate="$this->permissions">
                <flux:table.columns>
                    <flux:table.column class="ps-5!">Name</flux:table.column>
                    <flux:table.column>Assigned To</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->permissions as $permission)
                        <flux:table.row :key="$permission->id">

                            <flux:table.cell class="ps-5!">
                                {{ str($permission->name)->replace('.', ' ') }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-1 flex-wrap">
                                    @forelse ($permission->roles as $role)
                                        @php
                                            $roleColor = match ($role->name) {
                                                'admin' => 'red',
                                                'manager' => 'blue',
                                                'customer' => 'green',
                                                default => 'zinc',
                                            };
                                            $roleIcon = match ($role->name) {
                                                'admin' => 'shield-check',
                                                'manager' => 'user-circle',
                                                default => 'user',
                                            };
                                        @endphp
                                        <flux:badge size="sm" :color="$roleColor" variant="outline"
                                            :icon="$roleIcon" class="capitalize">
                                            {{ str($role->name)->replace('_', ' ')->title() }}
                                        </flux:badge>
                                    @empty
                                        <flux:text class="text-sm text-zinc-400">—</flux:text>
                                    @endforelse
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $permission->created_at->format('M d, Y') }}
                                </flux:text>
                            </flux:table.cell>

                        </flux:table.row>

                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon name="shield-exclamation" class="size-10 text-zinc-300" />
                                    <flux:heading size="lg" class="text-zinc-600">No Permissions Found
                                    </flux:heading>
                                    <flux:text class="text-sm text-zinc-400">
                                        {{ $this->search || $this->role ? 'No permissions match your filters.' : 'No permissions have been created yet.' }}
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
