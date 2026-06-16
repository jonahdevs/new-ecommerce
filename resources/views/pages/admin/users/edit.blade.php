<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Edit User — Admin')] class extends Component {
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $role = '';
    public string $banComment = '';

    public function mount(User $user): void
    {
        $this->user = $user->load('roles');
        $this->name  = $user->name;
        $this->email = $user->email;
        $this->role  = $user->roles->first()?->name ?? '';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::orderBy('name')->get();
    }

    public function save(): void
    {
        $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'role'  => ['required', Rule::exists('roles', 'name')],
        ]);

        $this->user->name  = $this->name;
        $this->user->email = $this->email;
        $this->user->save();
        $this->user->syncRoles([$this->role]);

        Flux::toast(heading: 'User updated', text: $this->name.' has been saved.', variant: 'success');

        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    public function sendResetLink(): void
    {
        $status = Password::sendResetLink(['email' => $this->user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Flux::toast(heading: 'Reset link sent', text: 'A password reset link has been sent to '.$this->user->email.'.', variant: 'success');
        } else {
            Flux::toast(heading: 'Could not send', text: __($status), variant: 'danger');
        }
    }

    public function ban(): void
    {
        $this->validate(['banComment' => ['nullable', 'string', 'max:500']]);

        $this->user->ban([
            'comment' => $this->banComment ?: null,
        ]);

        $this->user->refresh();
        $this->banComment = '';

        Flux::toast(heading: 'User banned', text: $this->user->name.' has been banned.', variant: 'warning');
    }

    public function unban(): void
    {
        $this->user->unban();
        $this->user->refresh();

        Flux::toast(heading: 'Ban lifted', text: $this->user->name.' can now access the system.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.roles.index')" wire:navigate>Roles</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Edit user</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Edit user</flux:heading>
                <flux:subheading>Update {{ $user->name }}'s details and role.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.roles.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Account details</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="name" label="Full name" required autofocus />
                        <flux:input wire:model="email" type="email" label="Email address" required />
                        <flux:select wire:model="role" label="Role">
                            @foreach ($this->roles as $r)
                                <flux:select.option value="{{ $r->name }}">{{ str($r->name)->headline() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </flux:card>
            </div>

            {{-- Side panel --}}
            <aside class="space-y-6">

                {{-- Password --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Password</flux:heading>
                    </div>
                    <div class="p-6">
                        <flux:text size="sm" class="text-zinc-500">
                            Send {{ $user->name }} a secure link to reset their own password.
                        </flux:text>
                        <flux:button size="sm" variant="ghost" icon="envelope" class="mt-4" wire:click="sendResetLink"
                            wire:loading.attr="disabled">
                            Send reset link
                        </flux:button>
                    </div>
                </flux:card>

                {{-- Access --}}
                @if ($user->id !== auth()->id())
                    <flux:card class="p-0 overflow-hidden">
                        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                            <flux:heading size="sm" class="uppercase tracking-wide">Access</flux:heading>
                        </div>

                        @if ($user->isBanned())
                            <div class="space-y-4 p-6">
                                <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                                    <flux:icon.no-symbol variant="micro" class="mt-0.5 size-4 shrink-0 text-red-500" />
                                    <div class="flex-1 text-sm">
                                        <p class="font-medium text-red-700 dark:text-red-400">This user is banned</p>
                                        @if ($activeBan = $user->bans()->latest()->first())
                                            @if ($activeBan->comment)
                                                <p class="mt-0.5 text-red-600 dark:text-red-500">Reason: {{ $activeBan->comment }}</p>
                                            @endif
                                            <p class="mt-0.5 text-red-500 dark:text-red-600">Since {{ $user->banned_at->diffForHumans() }}</p>
                                        @endif
                                    </div>
                                </div>
                                <flux:button size="sm" variant="ghost" icon="lock-open" wire:click="unban"
                                    wire:confirm="Lift the ban for '{{ addslashes($user->name) }}'?">
                                    Lift ban
                                </flux:button>
                            </div>
                        @else
                            <div class="space-y-4 p-6">
                                <flux:text size="sm" class="text-zinc-500">
                                    Banning this user will immediately block their access.
                                </flux:text>
                                <flux:textarea wire:model="banComment" label="Reason (optional)"
                                    placeholder="e.g. Violation of terms of service" rows="2" />
                                <flux:button size="sm" variant="danger" icon="no-symbol" wire:click="ban"
                                    wire:confirm="Ban '{{ addslashes($user->name) }}'? They will lose access immediately.">
                                    Ban user
                                </flux:button>
                            </div>
                        @endif
                    </flux:card>
                @endif

            </aside>
        </div>
    </form>
</div>
