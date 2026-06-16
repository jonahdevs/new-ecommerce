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

new #[Layout('layouts::app')] #[Title('Role — Admin')] class extends Component {
    /** Roles that cannot be renamed/deleted to avoid locking admins out. */
    private const PROTECTED_ROLES = ['admin', 'super-admin'];

    public ?int $roleId = null;
    public string $name = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public function mount(?Role $role = null): void
    {
        if ($role?->exists) {
            $this->roleId = $role->id;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('name')->all();
        }
    }

    /** @return Collection<string, \Illuminate\Support\Collection<int, Permission>> */
    #[Computed]
    public function groupedPermissions(): Collection
    {
        return Permission::orderBy('name')->get()->groupBy(fn (Permission $p) => Str::before($p->name, '.'));
    }

    public function isProtected(): bool
    {
        return $this->roleId !== null && in_array($this->name, self::PROTECTED_ROLES, true);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9 _-]+$/i', Rule::unique('roles', 'name')->ignore($this->roleId)],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [Rule::exists('permissions', 'name')],
        ], [
            'name.regex' => 'The role name may only contain letters, numbers, spaces, dashes and underscores.',
        ]);

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            if (! $this->isProtected()) {
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

        $this->redirectRoute('admin.roles.index', navigate: true);
    }

}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $roleId ? 'Edit' : 'New' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">

        {{-- Header --}}
        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">
                    {{ $roleId ? 'Edit '.Str::headline($name).' Role' : 'New role' }}
                </flux:heading>
                <flux:subheading>Manage permissions assigned to this role.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.roles.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">{{ $roleId ? 'Save changes' : 'Create role' }}</flux:button>
            </div>
        </div>

        {{-- Role name — inline below header, no card --}}
        <div class="mt-6 max-w-sm">
            <flux:input
                wire:model="name"
                label="Role name"
                placeholder="e.g. fulfilment"
                :disabled="$this->isProtected()"
                required
                autofocus />
            @if ($this->isProtected())
                <flux:text size="sm" class="mt-1.5 text-zinc-400">This is a protected role — its name cannot be changed.</flux:text>
            @endif
        </div>

        {{-- Permissions table --}}
        <flux:card class="mt-6 p-0 overflow-hidden">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Permissions</flux:heading>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->groupedPermissions as $group => $permissions)
                    <div class="flex items-center gap-4 px-6 py-4">

                        {{-- Resource label --}}
                        <div class="w-44 shrink-0">
                            <span class="text-sm font-semibold dark:text-white">{{ Str::headline($group) }}</span>
                        </div>

                        {{-- Action checkboxes --}}
                        <div class="flex flex-1 flex-wrap gap-x-10 gap-y-2">
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
        </flux:card>

    </form>
</div>
