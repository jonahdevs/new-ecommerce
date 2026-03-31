<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    public int $limit = 15;

    #[Computed]
    public function activities()
    {
        return Activity::with(['subject', 'causer'])
            ->whereIn('description', ['order_created', 'order_marked_paid', 'order_cancelled', 'payment_initiated', 'payment_confirmed', 'payment_failed', 'inventory_deducted', 'inventory_reserved', 'sap_sync_success', 'sap_sync_failed', 'quote_requested', 'quote_sent', 'quote_accepted', 'user_registered', 'webhook_received_mpesa', 'webhook_received_pesawise'])
            ->latest()
            ->limit($this->limit)
            ->get();
    }

    public function getEventIcon(string $description): string
    {
        return match (true) {
            str_contains($description, 'payment') => '💰',
            str_contains($description, 'order') => '📦',
            str_contains($description, 'inventory') => '📊',
            str_contains($description, 'sap') => '🔄',
            str_contains($description, 'quote') => '📝',
            str_contains($description, 'user') => '👤',
            str_contains($description, 'webhook') => '🔔',
            default => '•',
        };
    }

    public function getEventColor(string $description): string
    {
        return match (true) {
            str_contains($description, 'failed') || str_contains($description, 'cancelled') => 'text-red-600 dark:text-red-400',
            str_contains($description, 'confirmed') || str_contains($description, 'paid') || str_contains($description, 'success') || str_contains($description, 'accepted') => 'text-green-600 dark:text-green-400',
            str_contains($description, 'initiated') || str_contains($description, 'requested') => 'text-yellow-600 dark:text-yellow-400',
            default => 'text-blue-600 dark:text-blue-400',
        };
    }

    public function getEventLabel(string $description): string
    {
        return str_replace('_', ' ', ucwords($description, '_'));
    }
};
?>

<div>
    <flux:card class="p-0 h-full flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <flux:heading>Recent Activity</flux:heading>
            <flux:link :href="route('admin.activity-logs.index')" wire:navigate class="text-xs">View all</flux:link>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($this->activities as $activity)
                    <div
                        class="flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div class="text-xl shrink-0 mt-0.5">
                            {{ $this->getEventIcon($activity->description) }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <p class="text-xs font-medium {{ $this->getEventColor($activity->description) }}">
                                        {{ $this->getEventLabel($activity->description) }}
                                    </p>

                                    @if ($activity->causer)
                                        <p class="text-[10px] text-zinc-400 mt-0.5">
                                            by {{ $activity->causer->name ?? 'System' }}
                                        </p>
                                    @endif

                                    @if ($activity->subject)
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-500 mt-1">
                                            @if ($activity->subject_type === 'App\Models\Order')
                                                Order #{{ $activity->subject->reference ?? 'N/A' }}
                                                @if ($activity->properties->has('total'))
                                                    • {{ format_currency($activity->properties->get('total')) }}
                                                @endif
                                            @elseif($activity->subject_type === 'App\Models\Payment')
                                                @if ($activity->properties->has('order_reference'))
                                                    Order #{{ $activity->properties->get('order_reference') }}
                                                @endif
                                                @if ($activity->properties->has('amount'))
                                                    • {{ format_currency($activity->properties->get('amount')) }}
                                                @endif
                                            @elseif($activity->subject_type === 'App\Models\Quote')
                                                Quote #{{ $activity->subject->reference ?? 'N/A' }}
                                            @elseif($activity->subject_type === 'App\Models\User')
                                                {{ $activity->subject->email ?? 'User' }}
                                            @else
                                                {{ class_basename($activity->subject_type) }}
                                                #{{ $activity->subject_id }}
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <time class="text-[10px] text-zinc-400 whitespace-nowrap">
                                    {{ $activity->created_at->diffForHumans() }}
                                </time>
                            </div>

                            @if ($activity->properties->has('reason') || $activity->properties->has('error'))
                                <p class="text-[10px] text-red-600 dark:text-red-400 mt-1">
                                    {{ $activity->properties->get('reason') ?? $activity->properties->get('error') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-zinc-400 text-sm">
                        No recent activity
                    </div>
                @endforelse
            </div>
        </div>
    </flux:card>
</div>
