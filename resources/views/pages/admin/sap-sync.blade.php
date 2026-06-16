<?php

use App\Enums\SapSyncStatus;
use App\Jobs\SyncOrderToSapJob;
use App\Models\Order;
use App\Models\SapSyncLog;
use App\Services\Sap\SapConfig;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('SAP Sync — Admin')] class extends Component
{
    use WithPagination;

    /** Orders stuck mid-sync longer than this are surfaced for attention. */
    private const STUCK_AFTER_MINUTES = 60;

    #[Url]
    public string $tab = 'failed';

    #[Url]
    public int $perPage = 25;

    public function updatedPerPage(): void
    {
        $this->resetPage('failedPage');
        $this->resetPage('stuckPage');
        $this->resetPage('logsPage');
    }

    public function sapEnabled(): bool
    {
        return app(SapConfig::class)->isEnabled();
    }

    /** @return array<string, int> Count of orders per SAP sync status. */
    #[Computed]
    public function counts(): array
    {
        $raw = Order::query()
            ->selectRaw('sap_sync_status, count(*) as aggregate')
            ->groupBy('sap_sync_status')
            ->pluck('aggregate', 'sap_sync_status');

        return collect(SapSyncStatus::cases())
            ->mapWithKeys(fn (SapSyncStatus $s) => [$s->value => (int) ($raw[$s->value] ?? 0)])
            ->all();
    }

    /** Count of orders stalled mid-pipeline — drives the tab badge without loading the list. */
    #[Computed]
    public function stuckCount(): int
    {
        return $this->stuckQuery()->count();
    }

    /** @return LengthAwarePaginator<Order> Orders whose sync exhausted its retries. */
    #[Computed]
    public function failed(): LengthAwarePaginator
    {
        return Order::query()
            ->where('sap_sync_status', SapSyncStatus::FAILED)
            ->latest('updated_at')
            ->paginate($this->perPage, ['*'], 'failedPage');
    }

    /** @return LengthAwarePaginator<Order> Orders stalled mid-pipeline (likely needs a nudge). */
    #[Computed]
    public function stuck(): LengthAwarePaginator
    {
        return $this->stuckQuery()->latest('updated_at')->paginate($this->perPage, ['*'], 'stuckPage');
    }

    /** @return LengthAwarePaginator<SapSyncLog> Most recent sync attempts across all orders. */
    #[Computed]
    public function recentLogs(): LengthAwarePaginator
    {
        return SapSyncLog::query()
            ->with('order:id,order_number')
            ->latest()
            ->paginate($this->perPage, ['*'], 'logsPage');
    }

    private function stuckQuery()
    {
        return Order::query()
            ->whereIn('sap_sync_status', [SapSyncStatus::SYNCING, SapSyncStatus::AWAITING_CU])
            ->where('updated_at', '<', now()->subMinutes(self::STUCK_AFTER_MINUTES));
    }

    /** Re-queue a single order for SAP sync, mirroring the order-page resync. */
    public function resync(int $orderId): void
    {
        abort_unless(auth()->user()?->can('orders.manage'), 403);

        $order = Order::findOrFail($orderId);
        $order->update([
            'sap_sync_status' => SapSyncStatus::PENDING,
            'sap_sync_attempts' => 0,
            'sap_sync_error' => null,
        ]);

        SyncOrderToSapJob::dispatch($order);
        $this->refreshLists();

        Flux::toast(heading: 'Queued', text: "SAP sync re-queued for {$order->order_number}.", variant: 'success');
    }

    /** Re-queue every failed order in one go. */
    public function resyncAllFailed(): void
    {
        abort_unless(auth()->user()?->can('orders.manage'), 403);

        $orders = Order::where('sap_sync_status', SapSyncStatus::FAILED)->get();

        foreach ($orders as $order) {
            $order->update([
                'sap_sync_status' => SapSyncStatus::PENDING,
                'sap_sync_attempts' => 0,
                'sap_sync_error' => null,
            ]);
            SyncOrderToSapJob::dispatch($order);
        }

        $this->refreshLists();

        Flux::toast(
            heading: $orders->isEmpty() ? 'Nothing to resync' : 'Queued',
            text: $orders->isEmpty() ? 'No failed orders to resync.' : "Re-queued {$orders->count()} failed order(s).",
            variant: $orders->isEmpty() ? 'warning' : 'success',
        );
    }

    private function refreshLists(): void
    {
        unset($this->counts, $this->stuckCount, $this->failed, $this->stuck, $this->recentLogs);
    }
}; ?>

<div class="space-y-6">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>SAP sync</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">SAP sync</flux:heading>
            <flux:subheading>Monitor how orders are posting to SAP and KRA, and re-queue any that stall.</flux:subheading>
        </div>
        @if ($this->counts[\App\Enums\SapSyncStatus::FAILED->value] > 0)
            <flux:button variant="danger" icon="arrow-path" wire:click="resyncAllFailed"
                wire:confirm="Re-queue all failed orders for SAP sync?">
                Resync all failed
            </flux:button>
        @endif
    </div>

    @unless ($this->sapEnabled())
        <flux:callout icon="exclamation-triangle" color="amber">
            <flux:callout.heading>Auto-sync is currently disabled</flux:callout.heading>
            <flux:callout.text>
                New orders won't post to SAP automatically. You can still review history and manually resync below.
                Enable it under <a href="{{ route('admin.settings.system') }}" wire:navigate class="underline">Settings → System → SAP</a>.
            </flux:callout.text>
        </flux:callout>
    @endunless

    {{-- Status counts --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach (\App\Enums\SapSyncStatus::cases() as $status)
            <flux:card class="p-4">
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ $this->counts[$status->value] }}</div>
                <flux:badge :color="$status->badgeColor()" size="sm" inset="top bottom" class="mt-1">
                    {{ $status->label() }}
                </flux:badge>
            </flux:card>
        @endforeach
    </div>

    {{-- Tabbed lists --}}
    <flux:card class="overflow-hidden p-0">

        {{-- Tab bar --}}
        @php
            $tabs = [
                'failed' => ['label' => 'Failed', 'count' => $this->counts[\App\Enums\SapSyncStatus::FAILED->value], 'color' => 'red'],
                'stuck' => ['label' => 'Stuck', 'count' => $this->stuckCount, 'color' => 'amber'],
                'activity' => ['label' => 'Recent activity', 'count' => null, 'color' => null],
            ];
        @endphp
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-3 dark:border-zinc-700">
            <div class="flex gap-1 overflow-x-auto">
                @foreach ($tabs as $key => $meta)
                    <button type="button" wire:click="$set('tab', '{{ $key }}')"
                        @class([
                            'inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors',
                            'border-brand-500 text-brand-600 dark:text-brand-400' => $tab === $key,
                            'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' => $tab !== $key,
                        ])>
                        {{ $meta['label'] }}
                        @if (! is_null($meta['count']) && $meta['count'] > 0)
                            <flux:badge :color="$meta['color']" size="sm" inset="top bottom">{{ $meta['count'] }}</flux:badge>
                        @endif
                    </button>
                @endforeach
            </div>
            <flux:select wire:model.live="perPage" class="w-28 shrink-0">
                <flux:select.option value="10">10 / page</flux:select.option>
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
                <flux:select.option value="100">100 / page</flux:select.option>
            </flux:select>
        </div>

        {{-- FAILED --}}
        @if ($tab === 'failed')
            <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column>Error</flux:table.column>
                    <flux:table.column align="end">Attempts</flux:table.column>
                    <flux:table.column align="end">Last tried</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->failed as $order)
                        <flux:table.row :key="'failed-'.$order->id" wire:key="failed-{{ $order->id }}">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-mono hover:text-brand-500">{{ $order->order_number }}</a>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-md truncate text-zinc-500" title="{{ $order->sap_sync_error }}">
                                {{ $order->sap_sync_error ?: '—' }}
                            </flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $order->sap_sync_attempts }}</flux:table.cell>
                            <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $order->updated_at->format('d M, H:i') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                @can('orders.manage')
                                    <flux:button size="xs" variant="ghost" icon="arrow-path" tooltip="Resync"
                                        wire:click="resync({{ $order->id }})" />
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-10 text-center text-zinc-400">No failed syncs. 🎉</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if ($this->failed->hasPages())
                <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:pagination :paginator="$this->failed" />
                </div>
            @endif

        {{-- STUCK --}}
        @elseif ($tab === 'stuck')
            <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Since</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->stuck as $order)
                        <flux:table.row :key="'stuck-'.$order->id" wire:key="stuck-{{ $order->id }}">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-mono hover:text-brand-500">{{ $order->order_number }}</a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$order->sap_sync_status?->badgeColor() ?? 'zinc'" size="sm" inset="top bottom">
                                    {{ $order->sap_sync_status?->label() ?? '—' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $order->updated_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell align="end">
                                @can('orders.manage')
                                    <flux:button size="xs" variant="ghost" icon="arrow-path" tooltip="Resync"
                                        wire:click="resync({{ $order->id }})" />
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="py-10 text-center text-zinc-400">Nothing stuck.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if ($this->stuck->hasPages())
                <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:pagination :paginator="$this->stuck" />
                </div>
            @endif

        {{-- RECENT ACTIVITY --}}
        @else
            <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                    <flux:table.column>Order</flux:table.column>
                    <flux:table.column>Operation</flux:table.column>
                    <flux:table.column>Result</flux:table.column>
                    <flux:table.column>SAP doc</flux:table.column>
                    <flux:table.column align="end">When</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->recentLogs as $log)
                        <flux:table.row :key="'log-'.$log->id" wire:key="log-{{ $log->id }}">
                            <flux:table.cell variant="strong">
                                @if ($log->order)
                                    <a href="{{ route('admin.orders.show', $log->order_id) }}" wire:navigate class="font-mono hover:text-brand-500">{{ $log->order->order_number }}</a>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-500">{{ $log->operation ?: '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <span @class([
                                    'text-sm font-medium',
                                    'text-emerald-600' => $log->http_status_code && $log->http_status_code < 300,
                                    'text-red-500' => $log->http_status_code && $log->http_status_code >= 400,
                                    'text-zinc-500' => ! $log->http_status_code,
                                ])>
                                    {{ $log->http_status_code ?: $log->status }}
                                </span>
                                @if ($log->error_message)
                                    <span class="block max-w-md truncate text-xs text-zinc-400" title="{{ $log->error_message }}">{{ $log->error_message }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $log->sap_document_number ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $log->created_at->format('d M, H:i') }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-10 text-center text-zinc-400">No sync activity yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if ($this->recentLogs->hasPages())
                <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    <flux:pagination :paginator="$this->recentLogs" />
                </div>
            @endif
        @endif
    </flux:card>

</div>
