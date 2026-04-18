<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public int $userId;

    public function mount(): void
    {
        $this->userId = auth()->id();
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->unreadNotifications()->latest()->limit(10)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[On('echo-private:App.Models.User.{userId},NotificationReceived')]
    public function refreshNotifications(): void
    {
        unset($this->notifications, $this->unreadCount);
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
            ],
            'QuoteAcceptedNotification' => [
                'icon' => 'check-circle',
                'color' => 'green',
                'title' => $data['title'] ?? 'Quote Accepted',
                'message' => $data['message'] ?? 'A customer has accepted their quotation.',
                'url' => $data['url'] ?? route('admin.orders.index'),
            ],
            'QuoteRejectedNotification' => [
                'icon' => 'x-circle',
                'color' => 'red',
                'title' => $data['title'] ?? 'Quote Rejected',
                'message' => $data['message'] ?? 'A customer has rejected their quotation.',
                'url' => $data['url'] ?? route('admin.quotations.index'),
            ],
            'SapSyncFailedNotification' => [
                'icon' => 'exclamation-triangle',
                'color' => 'amber',
                'title' => $data['title'] ?? 'SAP Sync Failed',
                'message' => $data['message'] ?? 'An order failed to sync with SAP.',
                'url' => $data['url'] ?? route('admin.orders.index'),
            ],
            default => [
                'icon' => 'bell',
                'color' => 'zinc',
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? 'You have a new notification.',
                'url' => $data['url'] ?? '#',
            ],
        };
    }
};
?>

<flux:dropdown position="bottom" align="end">
    <flux:button variant="subtle" square class="relative" aria-label="Notifications">
        <flux:icon.bell variant="mini" class="text-zinc-500 dark:text-white" />
        @if ($this->unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-medium text-white">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </flux:button>

    <flux:menu class="w-80">
        <div class="flex items-center justify-between px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Notifications</span>
            @if ($this->unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse ($this->notifications as $notification)
                @php $data = $this->getNotificationData($notification); @endphp
                <flux:menu.item 
                    :href="$data['url']" 
                    wire:navigate
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="flex items-start gap-3 py-3!"
                >
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center shrink-0',
                        'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400' => $data['color'] === 'blue',
                        'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400' => $data['color'] === 'green',
                        'bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-400' => $data['color'] === 'amber',
                        'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400' => $data['color'] === 'red',
                        'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $data['color'] === 'zinc',
                    ])>
                        <flux:icon :name="$data['icon']" class="size-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">
                            {{ $data['title'] }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">
                            {{ $data['message'] }}
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-4 py-8 text-center">
                    <flux:icon.bell class="size-8 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No new notifications</p>
                </div>
            @endforelse
        </div>

        @if ($this->unreadCount > 10)
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-3 py-2">
                <flux:link href="{{ route('admin.notifications') }}" wire:navigate class="text-xs text-center block">
                    View all {{ $this->unreadCount }} notifications
                </flux:link>
            </div>
        @endif
    </flux:menu>
</flux:dropdown>
