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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        return User::query()
            ->whereDoesntHave('roles')
            ->withCount('orders')
            ->withSum('orders', 'total_cents')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        $base = User::whereDoesntHave('roles');

        return [
            'total' => (clone $base)->count(),
            'new_this_month' => (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'unverified' => (clone $base)->whereNull('email_verified_at')->count(),
        ];
    }

    public function delete(int $id): void
    {
        $customer = User::findOrFail($id);

        if ($customer->orders()->exists()) {
            Flux::toast(heading: 'Cannot delete', text: $customer->name.' has existing orders and cannot be deleted.', variant: 'danger');

            return;
        }

        $customer->delete();
        unset($this->customers);

        Flux::toast(heading: 'Customer deleted', text: $customer->name.' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
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
        <flux:button variant="primary" icon="user-plus" :href="route('admin.customers.create')" wire:navigate>New customer</flux:button>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card class="flex items-center gap-4">
            <flux:icon.users class="size-9 text-zinc-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['total'] }}</div>
                <flux:text size="sm">Total customers</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.user-plus class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['new_this_month'] }}</div>
                <flux:text size="sm">New this month</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.exclamation-circle class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['unverified'] }}</div>
                <flux:text size="sm">Unverified</flux:text>
            </div>
        </flux:card>
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

            <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column align="end">Orders</flux:table.column>
                <flux:table.column align="end">Total spent</flux:table.column>
                <flux:table.column align="end">Joined</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->customers as $customer)
                    <flux:table.row :key="$customer->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="sm" />
                                <div>
                                    <div class="font-medium text-sm dark:text-white">{{ $customer->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $customer->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $customer->orders_count }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($customer->orders_sum_total_cents) !!}</flux:table.cell>
                        <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $customer->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="eye" tooltip="View customer" :href="route('admin.customers.show', $customer)" wire:navigate />
                                <flux:button size="xs" variant="ghost" icon="pencil-square" tooltip="Edit customer" :href="route('admin.customers.edit', $customer)" wire:navigate />
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" tooltip="More actions" />
                                    <flux:menu>
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="delete({{ $customer->id }})"
                                            wire:confirm="Delete {{ addslashes($customer->name) }}? This cannot be undone.">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
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
