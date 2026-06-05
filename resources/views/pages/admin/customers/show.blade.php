<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Customer — Admin')] class extends Component {
    #[Locked]
    public User $customer;

    public string $banComment = '';

    public bool $showBanModal = false;

    public function mount(User $customer): void
    {
        $this->customer = $customer->load('addresses');
    }

    #[Computed]
    public function orders()
    {
        return $this->customer->orders()->withCount('items')->latest()->get();
    }

    #[Computed]
    public function totalSpentCents(): int
    {
        return (int) $this->customer->orders()->sum('total_cents');
    }

    public function ban(): void
    {
        $this->validate(['banComment' => ['nullable', 'string', 'max:500']]);

        $this->customer->ban([
            'comment' => $this->banComment ?: null,
        ]);

        $this->customer->refresh();
        $this->banComment = '';
        $this->showBanModal = false;

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
            <flux:breadcrumbs.item>{{ $customer->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="mt-2">
        <flux:heading size="xl">Customer details</flux:heading>
        <flux:subheading>Account information, addresses and order history.</flux:subheading>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left: profile --}}
        <aside class="space-y-6">

            @php $phone = $customer->addresses->firstWhere('is_default', true)?->phone ?? $customer->addresses->first()?->phone; @endphp

            {{-- User card --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="px-6 py-6 space-y-4">
                    <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="lg" />
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-semibold dark:text-white">{{ $customer->name }}</span>
                            @if ($customer->isBanned())
                                <flux:badge color="red" size="sm" inset="top bottom">Banned</flux:badge>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500">Joined {{ $customer->created_at->format('d F Y') }}</p>
                    </div>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex items-center gap-2">
                            <flux:icon.envelope variant="micro" class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $customer->email }}</span>
                        </div>
                        @if ($phone)
                            <div class="flex items-center gap-2">
                                <flux:icon.phone variant="micro" class="size-4 shrink-0 text-zinc-400" />
                                <span class="text-zinc-700 dark:text-zinc-300">{{ $phone }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <flux:separator />
                @if ($customer->isBanned())
                    <div class="px-6 py-3">
                        @if ($activeBan = $customer->bans()->latest()->first())
                            <div class="flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-xs dark:border-red-800 dark:bg-red-950/30">
                                <flux:icon.no-symbol variant="micro" class="mt-0.5 size-3.5 shrink-0 text-red-500" />
                                <div>
                                    @if ($activeBan->comment)
                                        <p class="font-medium text-red-700 dark:text-red-400">{{ $activeBan->comment }}</p>
                                    @endif
                                    <p class="text-red-500 dark:text-red-600">{{ $customer->banned_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="flex items-center gap-2 px-6 py-4">
                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                        :href="route('admin.customers.edit', $customer)" wire:navigate>Edit</flux:button>
                    @if ($customer->isBanned())
                        <flux:button size="sm" variant="ghost" icon="lock-open" wire:click="unban"
                            wire:confirm="Lift the ban for '{{ addslashes($customer->name) }}'?">
                            Lift ban
                        </flux:button>
                    @else
                        <flux:button size="sm" variant="danger" icon="no-symbol"
                            wire:click="$set('showBanModal', true)">
                            Ban customer
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- Addresses --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Addresses</flux:heading>
                </div>
                <div class="space-y-3 p-6">
                    @forelse ($customer->addresses as $address)
                        <div class="rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <span class="font-medium dark:text-white">{{ $address->label ?: $address->fullName() }}</span>
                                @if ($address->is_default)
                                    <flux:badge size="sm" inset="top bottom" color="green">Default</flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 text-zinc-500">{{ $address->oneLiner() }}</div>
                            @if ($address->phone)
                                <div class="text-zinc-500">{{ $address->phone }}</div>
                            @endif
                        </div>
                    @empty
                        <flux:text size="sm">No saved addresses.</flux:text>
                    @endforelse
                </div>
            </flux:card>

        </aside>

        {{-- Right: KPIs + orders --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- KPIs --}}
            <div class="grid grid-cols-3 gap-4">
                <flux:card class="flex items-center gap-4">
                    <flux:icon.shopping-bag class="size-8 shrink-0 text-zinc-400" />
                    <div>
                        <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->orders->count() }}</div>
                        <flux:text size="sm">Total orders</flux:text>
                    </div>
                </flux:card>
                <flux:card class="flex items-center gap-4">
                    <flux:icon.banknotes class="size-8 shrink-0 text-emerald-400" />
                    <div>
                        <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! money($this->totalSpentCents) !!}</div>
                        <flux:text size="sm">Lifetime spend</flux:text>
                    </div>
                </flux:card>
                <flux:card class="flex items-center gap-4">
                    <flux:icon.clock class="size-8 shrink-0 text-blue-400" />
                    <div>
                        <div class="text-sm font-semibold dark:text-white">
                            {{ $this->orders->first()?->created_at->format('M j, Y') ?? '—' }}
                        </div>
                        <flux:text size="sm">Last order</flux:text>
                    </div>
                </flux:card>
            </div>

            {{-- Order history --}}
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="sm">Order history</flux:heading>
                </div>
                <flux:table
                    container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>Order</flux:table.column>
                        <flux:table.column align="end">Items</flux:table.column>
                        <flux:table.column align="end">Total</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Placed</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->orders as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell variant="strong"><span class="font-mono">{{ $order->order_number }}</span></flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $order->items_count }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($order->total_cents) !!}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom" :color="$order->status->badgeColor()">
                                        {{ $order->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $order->created_at->format('M j, Y') }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="xs" variant="ghost" icon="eye" tooltip="View order"
                                        :href="route('admin.orders.show', $order)" wire:navigate />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                                    No orders yet.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

        </div>
    </div>

    {{-- Ban modal --}}
    <flux:modal wire:model="showBanModal" class="max-w-sm">
        <flux:heading>Ban {{ $customer->name }}</flux:heading>
        <flux:subheading class="mt-1">They will lose access to the store immediately.</flux:subheading>
        <div class="mt-5 space-y-4">
            <flux:textarea wire:model="banComment" label="Reason (optional)"
                placeholder="e.g. Fraudulent activity, repeated chargebacks…" rows="3" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                <flux:button variant="danger" icon="no-symbol" wire:click="ban">Ban customer</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
