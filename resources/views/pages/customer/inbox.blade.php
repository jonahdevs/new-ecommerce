<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed, On};
use Livewire\WithPagination;
use Artesaos\SEOTools\Facades\SEOMeta;

new #[Layout('layouts.customer')] class extends Component {
    use WithPagination;

    public string $selectedTab = 'unread';

    public int $userId;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,nofollow');
        $this->userId = auth()->id();
    }

    #[On('echo-private:App.Models.User.{userId},NotificationReceived')]
    public function refreshInbox(): void
    {
        unset($this->unreadNotifications, $this->readNotifications, $this->unreadCount, $this->hasNotifications);
    }

    #[Computed]
    public function hasNotifications(): bool
    {
        return auth()->user()->notifications()->exists();
    }

    #[Computed]
    public function unreadNotifications()
    {
        return auth()->user()->unreadNotifications()->latest()->paginate(10, pageName: 'unread');
    }

    #[Computed]
    public function readNotifications()
    {
        return auth()->user()->readNotifications()->latest()->paginate(10, pageName: 'read');
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

        unset($this->unreadNotifications, $this->readNotifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        unset($this->unreadNotifications, $this->readNotifications, $this->unreadCount);
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();

        unset($this->unreadNotifications, $this->readNotifications, $this->hasNotifications);
    }

    /**
     * Get notification display data based on type.
     */
    public function getNotificationData(object $notification): array
    {
        $data = $notification->data;
        $type = class_basename($notification->type);

        return match ($type) {
            'OrderStatusNotification' => [
                'icon' => 'truck',
                'color' => 'blue',
                'title' => $data['title'] ?? 'Order Update',
                'message' => $data['message'] ?? 'Your order status has been updated.',
                'url' => $data['url'] ?? null,
                'action' => 'View Order',
            ],
            'QuoteSentNotification' => [
                'icon' => 'tag',
                'color' => 'green',
                'title' => 'Quotation Ready',
                'message' => $data['message'] ?? 'Your quotation is ready for review.',
                'url' => $data['url'] ?? null,
                'action' => 'View Quotation',
            ],
            'QuoteExpiringNotification' => [
                'icon' => 'clock',
                'color' => 'amber',
                'title' => 'Quotation Expiring Soon',
                'message' => $data['message'] ?? 'Your quotation is expiring soon.',
                'url' => $data['url'] ?? null,
                'action' => 'View Quotation',
            ],
            'QuoteAcceptedNotification' => [
                'icon' => 'check-circle',
                'color' => 'green',
                'title' => 'Quotation Accepted',
                'message' => $data['message'] ?? 'Your quotation has been accepted.',
                'url' => $data['url'] ?? null,
                'action' => 'View Details',
            ],
            'QuoteRejectedNotification' => [
                'icon' => 'x-circle',
                'color' => 'red',
                'title' => 'Quotation Rejected',
                'message' => $data['message'] ?? 'Your quotation has been rejected.',
                'url' => $data['url'] ?? null,
                'action' => 'View Details',
            ],
            default => [
                'icon' => 'bell',
                'color' => 'zinc',
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? 'You have a new notification.',
                'url' => $data['url'] ?? null,
                'action' => 'View',
            ],
        };
    }
};
?>

<div>
    <flux:card class="p-0 rounded-md">
        {{-- Page Header --}}
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <flux:heading size="lg" level="1">Inbox</flux:heading>
            @if ($this->unreadCount > 0)
                <flux:button wire:click="markAllAsRead" variant="ghost" size="sm">
                    Mark all as read
                </flux:button>
            @endif
        </div>

        <div class="px-4 py-4">
            @if (!$this->hasNotifications)
                {{-- Empty State --}}
                <div class="min-h-[50svh] flex flex-col items-center gap-2 justify-center text-center">
                    <flux:icon.inbox class="size-12 text-zinc-300" />
                    <flux:heading>No notifications yet</flux:heading>
                    <flux:text class="text-zinc-500 max-w-sm">
                        When you receive order updates, quotation responses, or other important messages, they'll appear
                        here.
                    </flux:text>
                </div>
            @else
                {{-- Status Tabs --}}
                <div class="border-b border-zinc-200 dark:border-zinc-600 mb-4">
                    <nav class="flex gap-1 overflow-x-auto">
                        <button wire:click="$set('selectedTab', 'unread')" @class([
                            'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150 cursor-pointer',
                            'bg-brand-secondary text-brand-secondary-content font-medium' =>
                                $selectedTab === 'unread',
                            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' =>
                                $selectedTab !== 'unread',
                        ])>
                            <flux:icon.envelope class="size-4" />
                            Unread
                            @if ($this->unreadCount > 0)
                                <span @class([
                                    'text-xs px-1.5 py-0.5 rounded-full',
                                    'bg-white/20' => $selectedTab === 'unread',
                                    'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300' =>
                                        $selectedTab !== 'unread',
                                ])>
                                    {{ $this->unreadCount }}
                                </span>
                            @endif
                        </button>
                        <button wire:click="$set('selectedTab', 'read')" @class([
                            'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150 cursor-pointer',
                            'bg-brand-secondary text-brand-secondary-content font-medium' =>
                                $selectedTab === 'read',
                            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' =>
                                $selectedTab !== 'read',
                        ])>
                            <flux:icon.envelope-open class="size-4" />
                            Read
                        </button>
                    </nav>
                </div>

                {{-- Unread Tab Content --}}
                @if ($selectedTab === 'unread')
                    <div class="space-y-2">
                        @forelse ($this->unreadNotifications as $notification)
                            @php $data = $this->getNotificationData($notification); @endphp
                            <div wire:key="unread-{{ $notification->id }}"
                                class="border rounded-md p-4 bg-blue-50/50 dark:bg-blue-950/20 border-blue-100 dark:border-blue-900 hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors">
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
                                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                                    {{ $data['title'] }}
                                                </p>
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-0.5">
                                                    {{ $data['message'] }}
                                                </p>
                                            </div>
                                            <flux:text class="text-xs text-zinc-400 shrink-0">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </flux:text>
                                        </div>

                                        <div class="flex items-center gap-2 mt-3">
                                            @if ($data['url'])
                                                <flux:button :href="$data['url']" wire:navigate size="xs"
                                                    variant="filled">
                                                    {{ $data['action'] }}
                                                </flux:button>
                                            @endif
                                            <flux:button wire:click="markAsRead('{{ $notification->id }}')"
                                                size="xs" variant="ghost">
                                                Mark as read
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <flux:icon.check-circle class="w-12 h-12 text-green-300 mb-3" />
                                <flux:heading size="sm">All caught up!</flux:heading>
                                <flux:text class="text-zinc-500 mt-1 text-sm">
                                    You have no unread notifications.
                                </flux:text>
                            </div>
                        @endforelse
                    </div>

                    @if ($this->unreadNotifications->hasPages())
                        <div class="mt-4">
                            <flux:pagination :paginator="$this->unreadNotifications" />
                        </div>
                    @endif
                @endif

                {{-- Read Tab Content --}}
                @if ($selectedTab === 'read')
                    <div class="space-y-2">
                        @forelse ($this->readNotifications as $notification)
                            @php $data = $this->getNotificationData($notification); @endphp
                            <div wire:key="read-{{ $notification->id }}"
                                class="border rounded-md p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                <div class="flex items-start gap-3">
                                    {{-- Icon --}}
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-zinc-100 dark:bg-zinc-800 text-zinc-400">
                                        <flux:icon :name="$data['icon']" class="size-5" />
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                                    {{ $data['title'] }}
                                                </p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-500 mt-0.5">
                                                    {{ $data['message'] }}
                                                </p>
                                            </div>
                                            <flux:text class="text-xs text-zinc-400 shrink-0">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </flux:text>
                                        </div>

                                        <div class="flex items-center gap-2 mt-3">
                                            @if ($data['url'])
                                                <flux:button :href="$data['url']" wire:navigate size="xs"
                                                    variant="ghost">
                                                    {{ $data['action'] }}
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
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <flux:icon.inbox class="w-12 h-12 text-zinc-300 mb-3" />
                                <flux:heading size="sm">No read notifications</flux:heading>
                                <flux:text class="text-zinc-500 mt-1 text-sm">
                                    Notifications you've read will appear here.
                                </flux:text>
                            </div>
                        @endforelse
                    </div>

                    @if ($this->readNotifications->hasPages())
                        <div class="mt-4">
                            <flux:pagination :paginator="$this->readNotifications" />
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </flux:card>
</div>
