<?php

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Roles — Admin')] class extends Component {
    /** Roles that cannot be deleted to avoid locking admins out. */
    private const PROTECTED_ROLES = ['admin'];

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Permissions grouped by their prefix (segment before the dot).
     *
     * @return Collection<string, \Illuminate\Support\Collection<int, Permission>>
     */
    #[Computed]
    public function groupedPermissions(): Collection
    {
        return Permission::orderBy('name')->get()->groupBy(fn (Permission $p) => Str::before($p->name, '.'));
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9 _-]+$/i', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [Rule::exists('permissions', 'name')],
        ], [
            'name.regex' => 'The role name may only contain letters, numbers, spaces, dashes and underscores.',
        ]);

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);
            // Keep protected role names stable.
            if (! in_array($role->name, self::PROTECTED_ROLES, true)) {
                $role->name = $this->name;
                $role->save();
            }
            $role->syncPermissions($this->selectedPermissions);
            Flux::toast(heading: 'Role updated', text: $role->name.' has been saved.', variant: 'success');
        } else {
            $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
            $role->syncPermissions($this->selectedPermissions);
            Flux::toast(heading: 'Role created', text: $this->name.' has been added.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->roles);
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
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Roles</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2">Roles & permissions</flux:heading>
            <flux:subheading>Define what each role can access in the admin panel.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">Add role</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">
        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Role</flux:table.column>
                <flux:table.column align="end">Permissions</flux:table.column>
                <flux:table.column align="end">Members</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->roles as $role)
                    <flux:table.row :key="$role->id">
                        <flux:table.cell variant="strong">
                            <span class="capitalize">{{ $role->name }}</span>
                            @if ($this->isProtected($role->name))
                                <flux:badge size="sm" inset="top bottom" color="violet" class="ml-1">Protected</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $role->permissions_count }}</flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $role->users_count }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $role->id }})" />
                                @unless ($this->isProtected($role->name))
                                    <flux:button size="xs" variant="ghost" icon="trash"
                                        wire:click="delete({{ $role->id }})"
                                        wire:confirm="Delete the '{{ addslashes($role->name) }}' role?"
                                        class="text-red-500! hover:text-red-600!" />
                                @endunless
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-12 text-center text-zinc-400">No roles defined.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[560px]" :dismissible="false">
        <flux:heading>{{ $editingId ? 'Edit role' : 'New role' }}</flux:heading>

        <form wire:submit="save" class="mt-5 space-y-5">
            <flux:input
                wire:model="name"
                label="Role name"
                placeholder="e.g. fulfilment"
                :disabled="$editingId && $this->isProtected($name)"
                required
                autofocus />

            <div>
                <flux:label>Permissions</flux:label>
                <div class="mt-2 space-y-4">
                    @foreach ($this->groupedPermissions as $group => $permissions)
                        <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ Str::headline($group) }}</div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($permissions as $permission)
                                    <flux:checkbox
                                        wire:model="selectedPermissions"
                                        value="{{ $permission->name }}"
                                        label="{{ Str::headline(Str::after($permission->name, '.')) }}" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Save changes' : 'Create role' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
