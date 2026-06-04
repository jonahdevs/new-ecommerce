<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Payments — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterProvider = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProvider(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function payments()
    {
        return Payment::query()
            ->with('order.user')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('mpesa_receipt', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('account_reference', 'like', $term)
                        ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterProvider !== '', fn ($q) => $q->where('provider', $this->filterProvider))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        return [
            'collected' => (int) Payment::where('status', PaymentStatus::SUCCESS)->sum('amount_cents'),
            'pending' => Payment::where('status', PaymentStatus::PENDING)->count(),
            'failed' => Payment::where('status', PaymentStatus::FAILED)->count(),
        ];
    }

    /** @return array<int, string> */
    #[Computed]
    public function providers(): array
    {
        return Payment::query()->select('provider')->distinct()->orderBy('provider')->pluck('provider')->all();
    }

    /** @return array<int, PaymentStatus> */
    public function statuses(): array
    {
        return PaymentStatus::cases();
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Payments</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Payments</flux:heading>
            <flux:subheading>Every payment attempt across M-Pesa and card.</flux:subheading>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card class="flex items-center gap-4">
            <flux:icon.banknotes class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! money($this->stats['collected']) !!}</div>
                <flux:text size="sm">Collected</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.clock class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['pending'] }}</div>
                <flux:text size="sm">Pending</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.x-circle class="size-9 text-red-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->stats['failed'] }}</div>
                <flux:text size="sm">Failed</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search receipt, phone or order #…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterProvider" class="w-36">
                    <flux:select.option value="">All providers</flux:select.option>
                    @foreach ($this->providers as $provider)
                        <flux:select.option value="{{ $provider }}">{{ ucfirst($provider) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    @foreach ($this->statuses() as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
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
                <flux:table.column>Reference</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Provider</flux:table.column>
                <flux:table.column align="end">Amount</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Date</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row :key="$payment->id">
                        <flux:table.cell variant="strong">
                            <span class="font-mono text-xs">
                                {{ $payment->mpesa_receipt ?? $payment->stripe_payment_intent_id ?? $payment->checkout_request_id ?? '—' }}
                            </span>
                            @if ($payment->phone)
                                <span class="block text-xs font-normal text-zinc-400">{{ $payment->phone }}</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($payment->order)
                                <span class="font-mono text-sm">{{ $payment->order->order_number }}</span>
                                <span class="block text-xs text-zinc-500">{{ $payment->order->user?->name }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="capitalize text-zinc-500">{{ str_replace('_', ' ', (string) $payment->provider) }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($payment->amount_cents) !!}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$payment->status->badgeColor()">
                                {{ $payment->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="text-sm text-zinc-500">
                            {{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y g:i A') }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['payment', $payment->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="eye" tooltip="View payment" :href="route('admin.payments.show', $payment)" wire:navigate />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">No payments found.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->payments->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->payments" />
            </div>
        @endif
    </flux:card>
</div>
