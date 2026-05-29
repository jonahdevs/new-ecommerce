<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Staff — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'staff';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function staffMembers()
    {
        return User::role(['admin', 'staff'])
            ->with('roles')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::whereIn('name', ['admin', 'staff'])->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password']);
        $this->role = 'staff';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'staff';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ];

        if (! $this->editingId) {
            $rules['password'] = ['required', 'string', 'min:8'];
        } elseif ($this->password !== '') {
            $rules['password'] = ['string', 'min:8'];
        }

        $this->validate($rules);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->name = $this->name;
            $user->email = $this->email;
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            $user->save();
            $user->syncRoles([$this->role]);

            Flux::toast(heading: 'Staff updated', text: $user->name . ' has been saved.', variant: 'success');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($this->role);

            Flux::toast(heading: 'Staff invited', text: $this->name . ' has been added.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->staffMembers);
    }

    public function remove(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(heading: 'Cannot remove', text: 'You cannot remove your own account.', variant: 'danger');

            return;
        }

        $user = User::findOrFail($id);
        $user->syncRoles([]);

        unset($this->staffMembers);
        Flux::toast(heading: 'Staff removed', text: $user->name . '\'s staff access has been revoked.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Staff</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2">Staff & Roles</flux:heading>
            <flux:subheading>Manage who has access to the admin panel.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="user-plus" wire:click="openCreate">Invite staff</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Member</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->staffMembers as $member)
                    <flux:table.row :key="$member->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$member->name" :initials="$member->initials()" size="sm" />
                                <div>
                                    <div class="font-medium text-sm dark:text-white">
                                        {{ $member->name }}
                                        @if ($member->id === auth()->id())
                                            <flux:badge size="sm" color="blue" inset="top bottom" class="ml-1">You</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $member->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php $roleName = $member->roles->first()?->name ?? '—'; @endphp
                            <flux:badge
                                size="sm"
                                inset="top bottom"
                                :color="$roleName === 'admin' ? 'violet' : 'zinc'">
                                {{ ucfirst($roleName) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $member->created_at->format('M j, Y') }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="pencil-square"
                                    wire:click="openEdit({{ $member->id }})" />
                                @if ($member->id !== auth()->id())
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        icon="user-minus"
                                        wire:click="remove({{ $member->id }})"
                                        wire:confirm="Revoke staff access for '{{ addslashes($member->name) }}'?"
                                        class="text-red-500! hover:text-red-600!" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-12 text-center text-zinc-400">
                            No staff members found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->staffMembers->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->staffMembers" />
            </div>
        @endif
    </flux:card>

    {{-- Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[480px]" :dismissible="false">
        <flux:heading>{{ $editingId ? 'Edit staff member' : 'Invite staff member' }}</flux:heading>
        <flux:subheading>
            {{ $editingId ? 'Update this member\'s details and role.' : 'Add a new member to the admin panel.' }}
        </flux:subheading>

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model="name" label="Name" placeholder="Jane Doe" required autofocus />
            <flux:input wire:model="email" label="Email" type="email" placeholder="jane@example.com" required />

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="role">
                    <flux:select.option value="staff">Staff</flux:select.option>
                    <flux:select.option value="admin">Admin</flux:select.option>
                </flux:select>
                <flux:description>
                    Admins have full access. Staff have limited access.
                </flux:description>
            </flux:field>

            <flux:input
                wire:model="password"
                label="{{ $editingId ? 'New password' : 'Password' }}"
                type="password"
                :placeholder="$editingId ? 'Leave blank to keep current' : 'Min. 8 characters'"
                :required="! $editingId" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Save changes' : 'Invite member' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
