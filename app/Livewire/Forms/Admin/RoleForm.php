<?php

namespace App\Livewire\Forms\Admin;

use Livewire\Form;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class RoleForm extends Form
{
    public ?Role $role = null;

    public string $name = '';
    public array $permissions = [];

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($this->role?->id),
            ],
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Role name must be lowercase with underscores only. e.g. logistics_manager',
        ];
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->toArray();
    }

    public function store(): void
    {
        $this->validateOnly('name');

        Role::create([
            'name'      => $this->name,
            'is_system' => false,
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function update(): void
    {
        $this->validate();

        // Protect system roles from being renamed
        if (!$this->role->is_system) {
            $this->role->update(['name' => $this->name]);
        }

        $this->role->syncPermissions($this->permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
