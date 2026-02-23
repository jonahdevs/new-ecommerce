<?php

namespace App\Livewire\Forms\Admin;

use Livewire\Form;
use Spatie\Permission\Models\Role;

class RoleForm extends Form
{
    public ?Role $role = null;

    public array $permissions = [];

    public function rules(): array
    {
        return [
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
        $this->permissions = $role->permissions->pluck('name')->toArray();
    }

    public function update(): void
    {
        $this->validate();
        $this->role->syncPermissions($this->permissions);
    }
}
