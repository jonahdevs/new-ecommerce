<?php

use App\Models\Cart;
use App\Settings\CartReminderSettings;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Cart recovery — Admin')] class extends Component {
    use WithPagination;

    public bool $enabled = true;

    public int $first_delay_hours = 4;

    public int $second_delay_hours = 24;

    public int $min_subtotal = 0; // KES, for the form

    public int $stop_after_days = 7;

    public function mount(CartReminderSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->first_delay_hours = $settings->first_delay_hours;
        $this->second_delay_hours = $settings->second_delay_hours;
        $this->min_subtotal = intdiv($settings->min_subtotal_cents, 100);
        $this->stop_after_days = intdiv($settings->stop_after_hours, 24);
    }

    public function save(CartReminderSettings $settings): void
    {
        $this->validate([
            'first_delay_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'second_delay_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'min_subtotal' => ['required', 'integer', 'min:0'],
            'stop_after_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);

        $settings->fill([
            'enabled' => $this->enabled,
            'first_delay_hours' => $this->first_delay_hours,
            'second_delay_hours' => $this->second_delay_hours,
            'min_subtotal_cents' => $this->min_subtotal * 100,
            'stop_after_hours' => $this->stop_after_days * 24,
        ])->save();

        Flux::toast(heading: 'Saved', text: 'Cart recovery settings updated.', variant: 'success');
    }

    /**
     * Base query for carts that are currently abandoned: they hold items, have
     * been idle at least as long as the first reminder delay, and haven't been
     * recovered.
     */
    private function abandonedQuery()
    {
        return Cart::query()
            ->has('items')
            ->whereNull('recovered_at')
            ->where('last_activity_at', '<=', now()->subHours(max(1, $this->first_delay_hours)));
    }

    /** @return array{open: int, recoverable_cents: int, in_flight: int, recovered: int, recovery_rate: ?float} */
    #[Computed]
    public function stats(): array
    {
        $open = (clone $this->abandonedQuery())->count();
        $inFlight = (int) (clone $this->abandonedQuery())->where('reminders_sent', '>', 0)->count();

        // Load only price columns needed for the subtotal calculation.
        $openCarts = (clone $this->abandonedQuery())
            ->with([
                'items:id,cart_id,product_id,variant_id,quantity',
                'items.product:id,price,sale_price',
                'items.variant:id,price,compare_at_price',
            ])
            ->get(['id']);

        $recoverable = $openCarts->sum(fn (Cart $cart) => $cart->subtotalCents());
        $recovered = Cart::whereNotNull('recovered_at')->count();

        // Recovery rate: recovered carts as a share of every cart that has ever
        // entered the reminder cycle (recovered ones + those still in flight).
        $denominator = $recovered + $inFlight;

        return [
            'open' => $open,
            'recoverable_cents' => $recoverable,
            'in_flight' => $inFlight,
            'recovered' => $recovered,
            'recovery_rate' => $denominator > 0 ? round($recovered / $denominator * 100, 1) : null,
        ];
    }

    #[Computed]
    public function carts()
    {
        return $this->abandonedQuery()
            ->with(['user', 'items.product', 'items.variant'])
            ->orderByDesc('last_activity_at')
            ->paginate(10);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Marketing</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Cart recovery</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    @php $s = $this->stats; @endphp

    <div class="mt-2">
        <flux:heading size="xl">Cart recovery</flux:heading>
        <flux:subheading>Win back customers who left items behind with automated reminder emails.</flux:subheading>
    </div>

    {{-- KPIs --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <flux:card class="flex items-center gap-4">
            <flux:icon.shopping-cart class="size-8 shrink-0 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($s['open']) }}</div>
                <flux:text size="sm">Open abandoned carts</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.banknotes class="size-8 shrink-0 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{!! money($s['recoverable_cents']) !!}</div>
                <flux:text size="sm">Recoverable value</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.paper-airplane class="size-8 shrink-0 text-blue-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($s['in_flight']) }}</div>
                <flux:text size="sm">Reminders in flight</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.arrow-uturn-left class="size-8 shrink-0 text-violet-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">
                    {{ number_format($s['recovered']) }}
                    @if ($s['recovery_rate'] !== null)
                        <span class="text-sm font-normal text-zinc-400">· {{ $s['recovery_rate'] }}%</span>
                    @endif
                </div>
                <flux:text size="sm">Recovered carts</flux:text>
            </div>
        </flux:card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-3 lg:items-start">

        {{-- Settings --}}
        <flux:card class="overflow-hidden p-0 lg:col-span-1">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Reminder settings</flux:heading>
            </div>
            <form wire:submit="save" class="space-y-5 p-6">
                <div class="flex items-center justify-between gap-4 rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                    <div>
                        <flux:label>Enable reminders</flux:label>
                        <flux:text size="sm" class="text-xs">Only sent to customers who opted into marketing email.</flux:text>
                    </div>
                    <flux:switch wire:model.live="enabled" />
                </div>

                @if ($enabled)
                    <flux:input wire:model="first_delay_hours" type="number" min="1" max="720"
                        label="First reminder after (hours)"
                        description="Hours of inactivity before the first email." />
                    <flux:input wire:model="second_delay_hours" type="number" min="0" max="720"
                        label="Second reminder after (hours)"
                        description="0 disables the second email." />
                    <flux:input wire:model="min_subtotal" type="number" min="0"
                        label="Minimum cart value (KES)"
                        description="0 reminds any non-empty cart." />
                    <flux:input wire:model="stop_after_days" type="number" min="1" max="90"
                        label="Stop reminding after (days)"
                        description="Carts idle longer than this are left alone." />
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Abandoned carts --}}
        <flux:card class="overflow-hidden p-0 lg:col-span-2">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Abandoned carts</flux:heading>
            </div>
            <flux:table
                container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column align="end">Items</flux:table.column>
                    <flux:table.column align="end">Value</flux:table.column>
                    <flux:table.column align="end">Idle</flux:table.column>
                    <flux:table.column align="end">Reminders</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->carts as $cart)
                        <flux:table.row :key="$cart->id">
                            <flux:table.cell variant="strong">
                                {{ $cart->user?->name ?? 'Guest' }}
                                @if ($cart->user)
                                    <span class="block text-xs font-normal text-zinc-400">{{ $cart->user->email }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $cart->items->sum('quantity') }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium tabular-nums">{!! money($cart->subtotalCents()) !!}</flux:table.cell>
                            <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $cart->last_activity_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($cart->reminders_sent > 0)
                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ $cart->reminders_sent }} sent</flux:badge>
                                @else
                                    <span class="text-xs text-zinc-400">Pending</span>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
                                No abandoned carts right now.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            @if ($this->carts->hasPages())
                <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                    {{ $this->carts->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</div>
