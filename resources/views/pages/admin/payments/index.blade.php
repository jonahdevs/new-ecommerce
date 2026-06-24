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
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function applyDateRange(): void
    {
        $this->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo'   => ['nullable', 'date', 'after_or_equal:dateFrom'],
        ]);

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterProvider = '';
        $this->dateFrom = '';
        $this->dateTo = '';
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
            ->with(['order:id,order_number,user_id', 'order.user:id,name,email'])
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('mpesa_receipt', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('account_reference', 'like', $term)->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', $term));
                });
            })
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterProvider !== '', fn ($q) => $q->where('provider', $this->filterProvider))
            ->when($this->dateFrom !== '' && $this->dateTo !== '', fn ($q) => $q->whereBetween('paid_at', [
                \Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay(),
                \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay(),
            ]))
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return array<string, int> */
    #[Computed]
    public function stats(): array
    {
        $hasRange = $this->dateFrom !== '' && $this->dateTo !== '';
        $from     = $hasRange ? \Illuminate\Support\Carbon::parse($this->dateFrom)->startOfDay() : null;
        $to       = $hasRange ? \Illuminate\Support\Carbon::parse($this->dateTo)->endOfDay() : null;

        $base = fn () => Payment::query()->when($hasRange, fn ($q) => $q->whereBetween('paid_at', [$from, $to]));

        // Single query for aggregates that share the same scope.
        $agg = (clone $base())
            ->selectRaw('
                COALESCE(SUM(CASE WHEN status = ? THEN amount_cents ELSE 0 END), 0) as collected,
                COALESCE(SUM(COALESCE(refund_cents, 0)), 0) as refunded,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed
            ', [PaymentStatus::SUCCESS->value, PaymentStatus::PENDING->value, PaymentStatus::FAILED->value])
            ->first();

        return [
            'collected' => (int) ($agg->collected ?? 0),
            'pending'   => (int) ($agg->pending ?? 0),
            'failed'    => (int) ($agg->failed ?? 0),
            'refunded'  => (int) ($agg->refunded ?? 0),
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

@assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endassets

<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
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
        <div class="relative" wire:ignore x-data="rangePicker(@js($dateFrom), @js($dateTo))">
            <flux:icon.calendar-days class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-zinc-400" />
            <input x-ref="input" type="text" readonly placeholder="All time"
                class="w-52 cursor-pointer rounded-lg border border-zinc-200 bg-white py-1.5 pr-3 pl-8 text-sm text-zinc-700 transition-colors hover:border-zinc-400 focus:ring-2 focus:ring-zinc-300 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300" />
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
        <flux:card class="flex items-center gap-4">
            <flux:icon.arrow-uturn-left class="size-9 text-rose-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! money($this->stats['refunded']) !!}</div>
                <flux:text size="sm">Refunded</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div
            class="flex flex-col gap-3 border-b border-zinc-200 px-6 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search receipt, phone or order #…"
                icon="magnifying-glass" clearable class="sm:max-w-xs" />

            <div class="flex flex-wrap items-center gap-2">
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

                @if ($search || $filterStatus || $filterProvider || $dateFrom || $dateTo)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Clear</flux:button>
                @endif
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Reference</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Provider</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row :key="$payment->id">
                        <flux:table.cell variant="strong">
                            <span class="font-mono text-xs">
                                {{ $payment->mpesa_receipt ?? ($payment->paystack_reference ?? ($payment->stripe_payment_intent_id ?? ($payment->checkout_request_id ?? '—'))) }}
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
                        <flux:table.cell class="text-zinc-500">
                            {{ $payment->methodLabel() }}
                            <span class="block text-xs capitalize text-zinc-400">via
                                {{ str_replace('_', ' ', (string) $payment->provider) }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="font-medium tabular-nums">{!! money($payment->amount_cents) !!}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom" :color="$payment->status->badgeColor()">
                                {{ $payment->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y g:i A') }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['payment', $payment->id])" wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="eye" tooltip="View payment"
                                    :href="route('admin.payments.show', $payment)" wire:navigate />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center text-zinc-400">No payments found.
                        </flux:table.cell>
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
