<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Form;
use Illuminate\Validation\Rule;

class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $phone_number = '';
    public string $role = '';
    public string $status = 'active';
    public string $status_reason = '';
    public ?string $suspended_until = null;
    public bool $is_staff = true; // always true for admin-created users

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number ?? '';
        $this->role = $user->roles->first()?->name ?? '';
        $this->status = $user->status->value;
        $this->status_reason = $user->status_reason ?? '';
        $this->suspended_until = $user->suspended_until?->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'password'      => [$this->user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'phone_number'  => ['nullable', 'string', 'max:20'],
            'role'          => ['required', 'string', 'exists:roles,name'],
            'status'        => ['required', Rule::enum(UserStatus::class)],
            'status_reason' => ['nullable', 'string', 'max:500'],
            'suspended_until' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function store(): void
    {
        $this->validate();

        $user = User::create([
            'name'         => $this->name,
            'email'        => $this->email,
            'password'     => $this->password,
            'phone_number' => $this->phone_number ?: null,
            'is_staff'     => true,
            'status'       => $this->status,
            'status_reason'  => $this->status_reason ?: null,
            'suspended_until' => $this->status === 'suspended' ? $this->suspended_until : null,
        ]);

        $user->assignRole($this->role);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function update(): void
    {
        $this->validate();

        $this->user->update([
            'name'           => $this->name,
            'email'          => $this->email,
            'phone_number'   => $this->phone_number ?: null,
            'status'         => $this->status,
            'status_reason'  => $this->status_reason ?: null,
            'suspended_until' => $this->status === 'suspended' ? $this->suspended_until : null,
        ]);

        // Update password only if provided
        if ($this->password) {
            $this->user->update(['password' => $this->password]);
        }

        // Sync role
        $this->user->syncRoles([$this->role]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
