<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Customers')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatus(): void
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
        return User::role('customer')
            ->withCount('orders')
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                }),
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate($this->perPage);
    }

    public function delete(int $id): void
    {
        try {
            User::findOrFail($id)->delete();
            $this->dispatch('notify', variant: 'success', message: 'Customer deleted successfully.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete customer.', [
                'customer_id' => $id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Customers</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl">Customers</flux:heading>
    <flux:subheading>Manage your customer accounts</flux:subheading>

    <div class="mt-6">
        <flux:card class="p-0">

            {{-- Filters --}}
            <div class="flex items-center flex-wrap gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                    placeholder="Search customers..." class="max-w-xs" />

                <div class="ms-auto flex items-center gap-3">
                    <flux:select wire:model.live="status" placeholder="All Status" class="w-36">
                        <flux:select.option value="">All Status</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="inactive">Inactive</flux:select.option>
                        <flux:select.option value="banned">Banned</flux:select.option>
                    </flux:select>

                    <flux:button icon="plus-circle" variant="primary" size="sm"
                        :href="route('admin.customers.create')" wire:navigate>
                        Add Customer
                    </flux:button>
                </div>
            </div>

            {{-- Table --}}
            <flux:table :paginate="$this->customers">
                <flux:table.columns>
                    <flux:table.column class="ps-5!">ID</flux:table.column>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Phone</flux:table.column>
                    <flux:table.column>Orders</flux:table.column>
                    <flux:table.column>Amount Spent</flux:table.column>
                    <flux:table.column>Joined</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->customers as $customer)
                        <flux:table.row :key="$customer->id">

                            <flux:table.cell class="ps-5! text-zinc-400 text-sm">
                                #{{ $customer->id }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-9 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden shrink-0">
                                        @if ($customer->avatar)
                                            <img src="{{ asset('storage/' . $customer->avatar) }}"
                                                class="w-full h-full object-cover" alt="{{ $customer->name }}" />
                                        @else
                                            <div
                                                class="w-full h-full grid place-items-center text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <flux:text class="text-sm font-medium">{{ $customer->name }}</flux:text>
                                        <flux:text class="text-xs text-zinc-400">{{ $customer->email }}</flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $customer->phone_number ?? '—' }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm">{{ $customer->orders_count }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm">{{ number_format($customer->amount_spent ?? 0, 2) }}
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-500">
                                    {{ $customer->created_at->format('M d, Y') }}
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $statusColor = match ($customer->status) {
                                        'active' => 'green',
                                        'banned' => 'red',
                                        default => 'yellow',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$statusColor" variant="soft" class="capitalize">
                                    {{ $customer->status }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="pe-4!">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:tooltip content="View">
                                        <flux:button icon="eye" variant="ghost" size="sm"
                                            :href="route('admin.customers.show', $customer->id)" wire:navigate />
                                    </flux:tooltip>

                                    <flux:tooltip content="Edit">
                                        <flux:button icon="pencil-square" variant="ghost" size="sm"
                                            :href="route('admin.customers.edit', $customer->id)" wire:navigate />
                                    </flux:tooltip>

                                    <flux:tooltip content="Delete">
                                        <flux:button icon="trash" variant="ghost" size="sm" class="text-red-500!"
                                            wire:confirm="Are you sure? This will permanently delete {{ $customer->email }} and all related data."
                                            wire:click="delete({{ $customer->id }})" />
                                    </flux:tooltip>
                                </div>
                            </flux:table.cell>

                        </flux:table.row>

                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-16">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon name="users" class="size-10 text-zinc-300" />
                                    <flux:heading size="lg" class="text-zinc-600">No Customers Found</flux:heading>
                                    <flux:text class="text-sm text-zinc-400">
                                        {{ $this->search || $this->status ? 'No customers match your filters.' : 'No customers have been added yet.' }}
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            {{-- Footer --}}
            <div
                class="flex items-center justify-between flex-wrap gap-3 px-5 py-3 border-t border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-400">
                    Showing {{ $this->customers->firstItem() ?? 0 }}–{{ $this->customers->lastItem() ?? 0 }}
                    of {{ $this->customers->total() }} results
                </flux:text>

                <div class="flex items-center gap-2">
                    <flux:text class="text-sm shrink-0">Rows per page</flux:text>
                    <flux:select wire:model.live="perPage" class="w-20">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                        <flux:select.option value="100">100</flux:select.option>
                    </flux:select>
                </div>
            </div>

        </flux:card>
    </div>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
