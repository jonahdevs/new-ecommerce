<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
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

        Flux::toast(heading: 'Customer banned', text: $this->customer->name . ' has been banned.', variant: 'warning');
    }

    public function unban(): void
    {
        $this->customer->unban();
        $this->customer->refresh();

        Flux::toast(heading: 'Ban lifted', text: $this->customer->name . ' can now access the store.', variant: 'success');
    }

    public function delete(): void
    {
        $name = $this->customer->name;
        $this->customer->delete();
        Flux::toast(heading: 'Customer deleted', text: $name . ' has been removed.', variant: 'success');
        $this->redirectRoute('admin.customers.index', navigate: true);
    }

    public function getAvatarUrl(): ?string
    {
        return $this->customer->avatar ? Storage::disk('public')->url($this->customer->avatar) : null;
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

    <div class="mt-6 grid grid-cols-4 items-start gap-5">

        {{-- Left: profile --}}
        <div class="col-span-1">

            @php $default = $customer->addresses->firstWhere('is_default', true) ?? $customer->addresses->first(); @endphp

            <flux:card class="overflow-hidden p-0">

                {{-- Body --}}
                <div class="space-y-5 px-8 pb-6 pt-5">

                    {{-- Status badge --}}
                    <div class="flex justify-end">
                        @if ($customer->isBanned())
                            <flux:badge size="sm" color="red" variant="soft">Banned</flux:badge>
                        @else
                            <flux:badge size="sm" color="green" variant="soft">Active</flux:badge>
                        @endif
                    </div>

                    {{-- Avatar + name --}}
                    <div class="flex flex-col items-center gap-3">
                        @if ($this->getAvatarUrl())
                            <img src="{{ $this->getAvatarUrl() }}"
                                class="size-24 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                                alt="{{ $customer->name }}" />
                        @else
                            <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="xl" />
                        @endif
                        <div class="text-center">
                            <div class="text-base font-semibold dark:text-white">{{ $customer->name }}</div>
                            <div class="mt-0.5 text-xs text-zinc-500">Joined
                                {{ $customer->created_at->format('d F Y') }}</div>
                        </div>
                    </div>

                    {{-- Contact details --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <flux:icon.envelope variant="micro" class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $customer->email }}</span>
                        </div>
                        @if ($customer->phone)
                            <div class="flex items-center gap-2">
                                <flux:icon.phone variant="micro" class="size-4 shrink-0 text-zinc-400" />
                                <span class="text-zinc-700 dark:text-zinc-300">{{ $customer->phone }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Ban info --}}
                    @if ($customer->isBanned())
                        @if ($activeBan = $customer->bans()->latest()->first())
                            <div
                                class="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-xs dark:border-red-800 dark:bg-red-950/30">
                                <div class="flex items-start gap-2">
                                    <flux:icon.no-symbol variant="micro"
                                        class="mt-0.5 size-3.5 shrink-0 text-red-500" />
                                    <div>
                                        @if ($activeBan->comment)
                                            <p class="font-medium text-red-700 dark:text-red-400">
                                                {{ $activeBan->comment }}</p>
                                        @endif
                                        <p class="mt-0.5 text-red-500 dark:text-red-600">Since
                                            {{ $customer->banned_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Default address --}}
                    @if ($default)
                        <div class="space-y-1.5 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                            <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-400">Default address
                            </flux:text>
                            <div class="space-y-0.5 text-sm">
                                <div class="font-medium dark:text-white">{{ $default->label ?: $default->name }}</div>
                                @if ($default->line1)
                                    <div class="text-zinc-500">{{ $default->line1 }}</div>
                                @endif
                                @if ($default->phone)
                                    <div class="text-zinc-500">{{ $default->phone }}</div>
                                @endif
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Footer actions --}}
                <div class="flex gap-2 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:button size="sm" icon="pencil-square" class="flex-1"
                        :href="route('admin.customers.edit', $customer)" wire:navigate>
                        Edit
                    </flux:button>
                    @if ($customer->isBanned())
                        <flux:button size="sm" icon="lock-open" class="flex-1" wire:click="unban"
                            wire:confirm="Lift the ban for '{{ addslashes($customer->name) }}'?">
                            Lift ban
                        </flux:button>
                    @else
                        <flux:button size="sm" icon="no-symbol" class="flex-1"
                            wire:click="$set('showBanModal', true)">
                            Ban
                        </flux:button>
                    @endif
                    <flux:button size="sm" variant="danger" icon="trash-2" class="flex-1" wire:click="delete"
                        wire:confirm="Permanently delete {{ $customer->name }}? This cannot be undone.">
                        Delete
                    </flux:button>
                </div>

            </flux:card>

        </div>

        {{-- Right: KPIs + orders --}}
        <div class="col-span-3 space-y-5">

            {{-- KPIs --}}
            <div class="grid grid-cols-3 gap-4">
                <flux:card class="flex items-center gap-4">
                    <flux:icon.shopping-bag class="size-8 shrink-0 text-zinc-400" />
                    <div>
                        <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->orders->count() }}
                        </div>
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
                                <flux:table.cell variant="strong"><span
                                        class="font-mono">{{ $order->order_number }}</span></flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums text-zinc-500">
                                    {{ $order->items_count }}</flux:table.cell>
                                <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($order->total_cents) !!}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" inset="top bottom" :color="$order->status->badgeColor()">
                                        {{ $order->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="text-sm text-zinc-500">
                                    {{ $order->created_at->format('M j, Y') }}</flux:table.cell>
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
