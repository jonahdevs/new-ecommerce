<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\WithPagination;

new #[Layout('layouts.app.sidebar')] #[Title('Notifications')] class extends Component {
    use WithPagination;

    public string $filter = 'unread';

    #[Computed]
    public function notifications()
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->latest()->paginate(10);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->find($id);
        $notification?->markAsRead();
        
        unset($this->notifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        unset($this->notifications, $this->unreadCount);
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        
        unset($this->notifications);
    }

    public function getNotificationData(object $notification): array
    {
        $data = $notification->data;
        $type = class_basename($notification->type);

        return match ($type) {
            'QuoteRequestedNotification' => [
                'icon' => 'document-text',
                'color' => 'blue',
                'title' => $data['title'] ?? 'New Quote Request',
                'message' => $data['message'] ?? 'A new quote request has been submitted.',
                'url' => $data['url'] ?? route('admin.quotations.index'),
                'action' => 'View Quote',
            ],
            'QuoteAcceptedNotification' => [
                'icon' => 'check-circle',
                'color' => 'green',
                'title' => $data['title'] ?? 'Quote Accepted',
                'message' => $data['message'] ?? 'A customer has accepted their quotation.',
                'url' => $data['url'] ?? route('admin.orders.index'),
                'action' => 'View Order',
            ],
            'QuoteRejectedNotification' => [
                'icon' => 'x-circle',
                'color' => 'red',
                'title' => $data['title'] ?? 'Quote Rejected',
                'message' => $data['message'] ?? 'A customer has rejected their quotation.',
                'url' => $data['url'] ?? route('admin.quotations.index'),
                'action' => 'View Quote',
            ],
            'SapSyncFailedNotification' => [
                'icon' => 'exclamation-triangle',
                'color' => 'amber',
                'title' => $data['title'] ?? 'SAP Sync Failed',
                'message' => $data['message'] ?? 'An order failed to sync with SAP.',
                'url' => $data['url'] ?? route('admin.orders.index'),
                'action' => 'View Order',
            ],
            default => [
                'icon' => 'bell',
                'color' => 'zinc',
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? 'You have a new notification.',
                'url' => $data['url'] ?? '#',
                'action' => 'View',
            ],
        };
    }
};
?>

<div class="container mx-auto p-6">
    <flux:card class="p-0">
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-600 flex items-center justify-between">
            <flux:heading size="lg">Notifications</flux:heading>
            @if ($this->unreadCount > 0)
                <flux:button wire:click="markAllAsRead" variant="ghost" size="sm">
                    Mark all as read
                </flux:button>
            @endif
        </div>

        <div class="p-4">
            {{-- Filter Tabs --}}
            <div class="border-b border-zinc-200 dark:border-zinc-600 mb-4">
                <nav class="flex gap-1">
                    <button 
                        wire:click="$set('filter', 'unread')"
                        @class([
                            'px-3 py-2 text-sm transition-colors',
                            'bg-primary text-white font-medium' => $filter === 'unread',
                            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100' => $filter !== 'unread',
                        ])
                    >
                        Unread
                        @if ($this->unreadCount > 0)
                            <span @class([
                                'text-xs px-1.5 py-0.5 rounded-full ml-1',
                                'bg-white/20' => $filter === 'unread',
                                'bg-red-100 text-red-600' => $filter !== 'unread',
                            ])>{{ $this->unreadCount }}</span>
                        @endif
                    </button>
                    <button 
                        wire:click="$set('filter', 'read')"
                        @class([
                            'px-3 py-2 text-sm transition-colors',
                            'bg-primary text-white font-medium' => $filter === 'read',
                            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100' => $filter !== 'read',
                        ])
                    >
                        Read
                    </button>
                    <button 
                        wire:click="$set('filter', 'all')"
                        @class([
                            'px-3 py-2 text-sm transition-colors',
                            'bg-primary text-white font-medium' => $filter === 'all',
                            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100' => $filter !== 'all',
                        ])
                    >
                        All
                    </button>
                </nav>
            </div>

            {{-- Notifications List --}}
            <div class="space-y-2">
                @forelse ($this->notifications as $notification)
                    @php $data = $this->getNotificationData($notification); @endphp
                    <div @class([
                        'border rounded-lg p-4 transition-colors',
                        'bg-blue-50/50 border-blue-100 dark:bg-blue-950/20 dark:border-blue-900' => !$notification->read_at,
                        'bg-white border-zinc-200 dark:bg-zinc-800 dark:border-zinc-600' => $notification->read_at,
                    ])>
                        <div class="flex items-start gap-3">
                            {{-- Icon --}}
                            <div @class([
                                'w-10 h-10 rounded-full flex items-center justify-center shrink-0',
                                'bg-blue-100 text-blue-600' => $data['color'] === 'blue',
                                'bg-green-100 text-green-600' => $data['color'] === 'green',
                                'bg-amber-100 text-amber-600' => $data['color'] === 'amber',
                                'bg-red-100 text-red-600' => $data['color'] === 'red',
                                'bg-zinc-100 text-zinc-600' => $data['color'] === 'zinc',
                            ])>
                                <flux:icon :name="$data['icon']" class="size-5" />
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p @class([
                                            'text-sm',
                                            'font-semibold text-zinc-800' => !$notification->read_at,
                                            'font-medium text-zinc-600' => $notification->read_at,
                                        ])>
                                            {{ $data['title'] }}
                                        </p>
                                        <p class="text-sm text-zinc-500 mt-0.5">
                                            {{ $data['message'] }}
                                        </p>
                                    </div>
                                    <span class="text-xs text-zinc-400 shrink-0">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 mt-3">
                                    @if ($data['url'] && $data['url'] !== '#')
                                        <flux:button :href="$data['url']" wire:navigate size="xs" variant="filled">
                                            {{ $data['action'] }}
                                        </flux:button>
                                    @endif
                                    @if (!$notification->read_at)
                                        <flux:button wire:click="markAsRead('{{ $notification->id }}')" size="xs" variant="ghost">
                                            Mark as read
                                        </flux:button>
                                    @endif
                                    <flux:button wire:click="deleteNotification('{{ $notification->id }}')" 
                                        size="xs" variant="ghost" class="text-red-500 hover:text-red-600">
                                        Delete
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <flux:icon.bell class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:heading size="sm">No notifications</flux:heading>
                        <flux:text class="text-zinc-500 mt-1">
                            @if ($filter === 'unread')
                                You're all caught up!
                            @elseif ($filter === 'read')
                                No read notifications yet.
                            @else
                                No notifications to display.
                            @endif
                        </flux:text>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($this->notifications->hasPages())
                <div class="mt-4">
                    {{ $this->notifications->links() }}
                </div>
            @endif
        </div>
    </flux:card>
</div>
