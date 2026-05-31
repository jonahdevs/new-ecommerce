<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('General settings — Admin')] class extends Component
{
    #[Url]
    public string $section = 'profile';

    /** @var array<string, array<string, bool>> */
    public array $notifications = [];

    /** @var array<string, array<string, bool>> */
    protected static array $notificationDefaults = [
        'new_order' => ['email' => true, 'app' => true],
        'low_stock' => ['email' => true, 'app' => true],
        'new_review' => ['email' => false, 'app' => true],
        'new_quote' => ['email' => true, 'app' => true],
    ];

    /** @var array<string, string> */
    protected static array $notificationLabels = [
        'new_order' => 'New order placed',
        'low_stock' => 'Product low on stock',
        'new_review' => 'New product review',
        'new_quote' => 'New quotation request',
    ];

    public function mount(): void
    {
        $prefs = Auth::user()->staff_preferences ?? [];

        $this->notifications = array_replace_recursive(
            static::$notificationDefaults,
            $prefs['notifications'] ?? [],
        );
    }

    /** @return array<string, string> */
    public function getNotificationLabels(): array
    {
        return static::$notificationLabels;
    }

    public function saveNotifications(): void
    {
        $prefs = Auth::user()->staff_preferences ?? [];
        $prefs['notifications'] = $this->notifications;
        Auth::user()->update(['staff_preferences' => $prefs]);

        Flux::toast(heading: 'Saved', text: 'Notification preferences updated.', variant: 'success');
    }
}; ?>

<x-admin.settings-shell tab="general" :section="$section">

    {{-- Profile (personal account) --}}
    @if ($section === 'profile')
        <livewire:pages::account.settings.profile :embedded="true" />
    @endif

    {{-- Security (personal account) --}}
    @if ($section === 'security')
        <livewire:pages::account.settings.security :embedded="true" />
    @endif

    {{-- Appearance (personal account) --}}
    @if ($section === 'appearance')
        <livewire:pages::account.settings.appearance :embedded="true" />
    @endif

    {{-- My notifications (personal staff alerts) --}}
    @if ($section === 'notifications')
        <flux:card>
            <flux:heading>My notifications</flux:heading>
            <flux:subheading>Choose which alerts you personally receive, and how.</flux:subheading>

            <form wire:submit="saveNotifications" class="mt-6">
                <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                    <div class="grid grid-cols-[1fr_auto_auto] items-center gap-x-6 border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 text-xs font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <span>Event</span>
                        <span class="w-12 text-center">Email</span>
                        <span class="w-12 text-center">In-app</span>
                    </div>
                    @foreach ($this->getNotificationLabels() as $event => $label)
                        <div class="grid grid-cols-[1fr_auto_auto] items-center gap-x-6 px-4 py-3 @if (! $loop->last) border-b border-zinc-100 dark:border-zinc-800 @endif">
                            <span class="text-sm dark:text-white">{{ $label }}</span>
                            <div class="flex w-12 justify-center">
                                <flux:checkbox wire:model="notifications.{{ $event }}.email" />
                            </div>
                            <div class="flex w-12 justify-center">
                                <flux:checkbox wire:model="notifications.{{ $event }}.app" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

</x-admin.settings-shell>
