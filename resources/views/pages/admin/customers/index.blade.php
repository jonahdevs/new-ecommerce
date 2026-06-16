<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Customers — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        return User::query()
            ->withBanned()
            ->whereDoesntHave('roles')
            ->withCount('orders')
            ->withSum('orders', 'total_cents')
            ->when($this->search, function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(fn($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($this->filterStatus === 'active', fn($q) => $q->whereNull('banned_at'))
            ->when($this->filterStatus === 'banned', fn($q) => $q->whereNotNull('banned_at'))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        $base = User::withBanned()->whereDoesntHave('roles');

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereNull('banned_at')->count(),
            'banned' => (clone $base)->whereNotNull('banned_at')->count(),
            'new_this_month' => (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];
    }

    public function delete(int $id): void
    {
        $customer = User::findOrFail($id);

        if ($customer->orders()->exists()) {
            Flux::toast(heading: 'Cannot delete', text: $customer->name . ' has existing orders and cannot be deleted.', variant: 'danger');

            return;
        }

        $customer->delete();
        unset($this->customers);

        Flux::toast(heading: 'Customer deleted', text: $customer->name . ' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Customers</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Customers</flux:heading>
            <flux:subheading>Everyone who has registered a storefront account.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="user-plus" :href="route('admin.customers.create')" wire:navigate>New customer
        </flux:button>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="flex items-center gap-4">
            <flux:icon.users class="size-9 text-zinc-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['total'] }}</div>
                <flux:text size="sm">Total customers</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.user-circle class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['active'] }}</div>
                <flux:text size="sm">Active customers</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.no-symbol class="size-9 text-red-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['banned'] }}</div>
                <flux:text size="sm">Banned</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.user-plus class="size-9 text-blue-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['new_this_month'] }}
                </div>
                <flux:text size="sm">New this month</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Export --}}
        <div
            class="flex flex-wrap items-center justify-end gap-2 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:dropdown>
                <flux:button size="sm" icon="arrow-down-tray" icon-trailing="chevron-down">Export</flux:button>
                <flux:menu>
                    <flux:menu.item icon="table-cells"
                        href="{{ route('admin.customers.export', array_filter(['format' => 'xlsx', 'q' => $search, 'status' => $filterStatus])) }}">
                        Excel (.xlsx)
                    </flux:menu.item>
                    <flux:menu.item icon="document-text"
                        href="{{ route('admin.customers.export', array_filter(['format' => 'csv', 'q' => $search, 'status' => $filterStatus])) }}">
                        CSV (.csv)
                    </flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="document-chart-bar"
                        href="{{ route('admin.customers.pdf', array_filter(['q' => $search, 'status' => $filterStatus])) }}">
                        PDF report
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email…"
                icon="magnifying-glass" clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-32">
                    <flux:select.option value="">All status</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="banned">Banned</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Orders</flux:table.column>
                <flux:table.column>Total spent</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->customers as $customer)
                    <flux:table.row :key="$customer->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="sm"
                                    circle />
                                <div>
                                    <div class="font-medium text-sm dark:text-white">{{ $customer->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $customer->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($customer->isBanned())
                                <flux:badge size="sm" inset="top bottom" color="red">Banned</flux:badge>
                            @else
                                <flux:badge size="sm" inset="top bottom" color="green">Active</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums text-zinc-500">{{ $customer->orders_count }}
                        </flux:table.cell>
                        <flux:table.cell class="font-medium tabular-nums">{!! money($customer->orders_sum_total_cents) !!}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ $customer->created_at->format('M j, Y') }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown align="end">
                                <flux:button size="sm" icon-trailing="chevron-down">Actions</flux:button>
                                <flux:menu>
                                    <flux:menu.item icon="eye" icon-variant="micro"
                                        :href="route('admin.customers.show', $customer)" wire:navigate>View
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil-square" icon-variant="micro"
                                        :href="route('admin.customers.edit', $customer)" wire:navigate>Edit
                                    </flux:menu.item>
                                    <flux:menu.item icon="clock" icon-variant="micro"
                                        :href="route('admin.activity.item', ['user', $customer->id])" wire:navigate>
                                        Activity log</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash-2" icon-variant="micro" variant="danger"
                                        wire:click="delete({{ $customer->id }})"
                                        wire:confirm="Delete {{ addslashes($customer->name) }}? This cannot be undone.">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            No customers found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->customers->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->customers" />
            </div>
        @endif
    </flux:card>
</div>
