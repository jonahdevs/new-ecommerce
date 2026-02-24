<?php

use Livewire\Component;
use Livewire\Attributes\{Title, Computed};
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Livewire\Forms\Admin\RoleForm;

new class extends Component {
    public RoleForm $form;
    public Role $role;

    public function mount(Role $role): void
    {
        $this->role = $role;
        $this->form->setRole($role);
    }

    #[Computed]
    public function groupedPermissions(): array
    {
        return Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name, 2)[1] ?? $p->name)
            ->map(fn($group) => $group->pluck('name', 'name'))
            ->sortKeys() // alphabetical order for consistency
            ->toArray();
    }

    public function save(): void
    {
        try {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', message: 'Role updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to update role permissions.', [
                'role_id' => $this->role->id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit Role Permissions</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Edit {{ str($role->name)->replace('_', ' ')->title() }} Permissions</flux:heading>
    <flux:subheading>Manage permissions assigned to this role</flux:subheading>

    <form wire:submit="save" class="mt-6">
        <flux:card class="p-0">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($this->groupedPermissions as $resource => $actions)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-5 py-4">

                        {{-- Resource Label --}}
                        <div>
                            <flux:heading class="capitalize">
                                {{ Str::of($resource)->replace('-', ' ')->title() }}
                            </flux:heading>
                        </div>

                        {{-- Permission Checkboxes --}}
                        <div class="md:col-span-2 grid grid-cols-2 xl:grid-cols-4 gap-3">
                            @foreach ($actions as $permissionName)
                                @php
                                    $action = explode('.', $permissionName, 2)[0];
                                @endphp
                                <flux:checkbox wire:model="form.permissions" :value="$permissionName"
                                    :label="Str::ucfirst($action)" :id="$permissionName" />
                            @endforeach
                        </div>

                    </div>
                @empty
                    <div class="flex flex-col items-center gap-3 py-16">
                        <flux:icon name="key" class="size-10 text-zinc-300" />
                        <flux:heading size="lg" class="text-zinc-600">No Permissions Found</flux:heading>
                        <flux:text class="text-sm text-zinc-400">
                            No permissions have been created yet. Run your permission seeder to get started.
                        </flux:text>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-zinc-100 dark:border-zinc-700">
                <flux:button type="submit" variant="primary" class="min-w-44">
                    Update Role
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
