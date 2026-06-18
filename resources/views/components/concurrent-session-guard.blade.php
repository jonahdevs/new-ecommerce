<?php

use App\Settings\SecuritySettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public array $selected = [];

    #[Computed]
    public function limit(): int
    {
        return app(SecuritySettings::class)->max_concurrent_sessions;
    }

    #[Computed]
    public function otherSessions(): Collection
    {
        if (! auth()->check() || $this->limit === 0) {
            return collect();
        }

        $lifetime = config('session.lifetime') * 60;

        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->where('last_activity', '>=', now()->timestamp - $lifetime)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'ip'          => $s->ip_address ?? '—',
                'browser'     => $this->parseBrowser($s->user_agent),
                'os'          => $this->parseOs($s->user_agent),
                'last_active' => $s->last_activity,
            ]);
    }

    #[Computed]
    public function isBlocked(): bool
    {
        return $this->limit > 0 && $this->otherSessions->count() >= $this->limit;
    }

    public function revoke(): void
    {
        if (empty($this->selected)) {
            return;
        }

        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->whereIn('id', $this->selected)
            ->delete();

        $this->selected = [];
        unset($this->otherSessions);
    }

    public function revokeAll(): void
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->selected = [];
        unset($this->otherSessions);
    }

    private function parseBrowser(?string $ua): string
    {
        if (! $ua) {
            return 'Browser';
        }

        return match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => 'Browser',
        };
    }

    private function parseOs(?string $ua): string
    {
        if (! $ua) {
            return 'Unknown';
        }

        return match (true) {
            str_contains($ua, 'Windows NT') => 'Windows',
            str_contains($ua, 'Mac OS X')   => 'macOS',
            str_contains($ua, 'iPhone')      => 'iOS',
            str_contains($ua, 'iPad')        => 'iPadOS',
            str_contains($ua, 'Android')     => 'Android',
            str_contains($ua, 'Linux')       => 'Linux',
            default                          => 'Unknown',
        };
    }
}; ?>

<div wire:poll.30000ms x-data="{ sel: $wire.entangle('selected') }">
    @auth
        @if ($this->isBlocked)
            <flux:modal
                name="session-guard"
                :dismissible="false"
                :closable="false"
                x-init="$flux.modal('session-guard').show()"
                class="max-w-md"
            >
                <div class="space-y-5">
                    {{-- Header — centered --}}
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20">
                            <flux:icon.device-phone-mobile class="size-7 text-amber-600 dark:text-amber-400" />
                        </div>
                        <flux:heading size="lg">New device sign-in</flux:heading>
                        <flux:text class="mt-1.5">
                            You are signed in on another device. Please log out from that session to continue here.
                        </flux:text>
                    </div>

                    {{-- Session list --}}
                    <div class="-mx-6 divide-y divide-zinc-100 border-y border-zinc-100 dark:divide-zinc-700 dark:border-zinc-700">
                        @foreach ($this->otherSessions as $session)
                            <label class="flex cursor-pointer items-center gap-4 px-6 py-3.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <flux:checkbox value="{{ $session['id'] }}" x-model="sel" />
                                <div class="flex flex-1 items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.computer-desktop class="size-5 text-zinc-500" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ $session['browser'] }} &middot; {{ $session['os'] }}
                                        </p>
                                        <p class="text-xs text-zinc-400">
                                            {{ $session['ip'] }} &middot; {{ \Carbon\Carbon::createFromTimestamp($session['last_active'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    {{-- Actions --}}
                    <div class="space-y-2">
                        <flux:button
                            wire:click="revoke"
                            x-bind:disabled="sel.length === 0"
                            variant="primary"
                            class="w-full"
                        >
                            Remove selected
                            <span x-show="sel.length > 0" x-text="'(' + sel.length + ')'" x-cloak></span>
                        </flux:button>
                        <flux:button wire:click="revokeAll" variant="ghost" class="w-full">
                            Log out all other devices
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif
    @endauth
</div>
