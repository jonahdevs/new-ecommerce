<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Edit Customer — Admin')] class extends Component {
    #[Locked]
    public User $customer;

    public string $name = '';
    public string $email = '';
    public string $banComment = '';

    public function mount(User $customer): void
    {
        $this->customer = $customer;
        $this->name     = $customer->name;
        $this->email    = $customer->email;
    }

    public function save(): void
    {
        $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->customer->id)],
        ]);

        $this->customer->name  = $this->name;
        $this->customer->email = $this->email;
        $this->customer->save();

        Flux::toast(heading: 'Customer updated', text: $this->customer->name.' has been saved.', variant: 'success');

        $this->redirectRoute('admin.customers.show', $this->customer, navigate: true);
    }

    public function sendResetLink(): void
    {
        $status = Password::sendResetLink(['email' => $this->customer->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Flux::toast(heading: 'Reset link sent', text: 'A password reset link has been sent to '.$this->customer->email.'.', variant: 'success');
        } else {
            Flux::toast(heading: 'Could not send', text: __($status), variant: 'danger');
        }
    }

    public function ban(): void
    {
        $this->validate(['banComment' => ['nullable', 'string', 'max:500']]);

        $this->customer->ban([
            'comment' => $this->banComment ?: null,
        ]);

        $this->customer->refresh();
        $this->banComment = '';

        Flux::toast(heading: 'Customer banned', text: $this->customer->name.' has been banned.', variant: 'warning');
    }

    public function unban(): void
    {
        $this->customer->unban();
        $this->customer->refresh();

        Flux::toast(heading: 'Ban lifted', text: $this->customer->name.' can now access the store.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.index')" wire:navigate>Customers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.customers.show', $customer)" wire:navigate>{{ $customer->name }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Edit customer</flux:heading>
                <flux:subheading>Update {{ $customer->name }}'s details.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.customers.show', $customer)" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Profile</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="name" label="Full name" required autofocus />
                        <flux:input wire:model="email" type="email" label="Email address" required />
                    </div>
                </flux:card>
            </div>

            {{-- Side panel --}}
            <aside class="space-y-6">

                {{-- Password --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Password</flux:heading>
                    </div>
                    <div class="p-6">
                        <flux:text size="sm" class="text-zinc-500">
                            Send {{ $customer->name }} a secure link to reset their own password.
                        </flux:text>
                        <flux:button size="sm" variant="ghost" icon="envelope" class="mt-4" wire:click="sendResetLink"
                            wire:loading.attr="disabled">
                            Send reset link
                        </flux:button>
                    </div>
                </flux:card>

                {{-- Access --}}
                <flux:card class="p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:heading size="sm">Access</flux:heading>
                    </div>

                    @if ($customer->isBanned())
                        <div class="space-y-4 p-6">
                            <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                                <flux:icon.no-symbol variant="micro" class="mt-0.5 size-4 shrink-0 text-red-500" />
                                <div class="flex-1 text-sm">
                                    <p class="font-medium text-red-700 dark:text-red-400">This customer is banned</p>
                                    @if ($activeBan = $customer->bans()->latest()->first())
                                        @if ($activeBan->comment)
                                            <p class="mt-0.5 text-red-600 dark:text-red-500">Reason: {{ $activeBan->comment }}</p>
                                        @endif
                                        <p class="mt-0.5 text-red-500 dark:text-red-600">Since {{ $customer->banned_at->diffForHumans() }}</p>
                                    @endif
                                </div>
                            </div>
                            <flux:button size="sm" variant="ghost" icon="lock-open" wire:click="unban"
                                wire:confirm="Lift the ban for '{{ addslashes($customer->name) }}'?">
                                Lift ban
                            </flux:button>
                        </div>
                    @else
                        <div class="space-y-4 p-6">
                            <flux:text size="sm" class="text-zinc-500">
                                Banning this customer will immediately block their access to the store.
                            </flux:text>
                            <flux:textarea wire:model="banComment" label="Reason (optional)" placeholder="e.g. Fraudulent activity" rows="2" />
                            <flux:button size="sm" variant="danger" icon="no-symbol" wire:click="ban"
                                wire:confirm="Ban '{{ addslashes($customer->name) }}'? They will lose access immediately.">
                                Ban customer
                            </flux:button>
                        </div>
                    @endif
                </flux:card>

            </aside>
        </div>
    </form>
</div>
