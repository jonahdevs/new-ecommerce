<?php

use App\Models\BannedIp;
use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts::app')] #[Title('Maintenance | Admin')] class extends Component
{
    #[Url]
    public string $section = 'banned-ips';

    // Banned IPs — add
    public bool $showBanIpModal = false;

    public string $banIp = '';

    public string $banComment = '';

    public string $banExpiresAt = '';

    // Banned IPs — edit
    public bool $showEditBanModal = false;

    public ?int $editingBanId = null;

    public string $editComment = '';

    public string $editExpiresAt = '';

    // Backup
    public string $backupTab = 'system';

    public bool $showGenerateBackupModal = false;

    public function switchBackupTab(string $tab): void
    {
        $this->backupTab = $tab;
        unset($this->backups);
    }

    private function diskName(): string
    {
        return config('backup.backup.destination.disks')[0] ?? 'local';
    }

    private function backupNameFor(string $type): string
    {
        return 'backup - '.($type === 'database' ? 'Database' : 'System');
    }

    private function destination(string $type): BackupDestination
    {
        return BackupDestination::create($this->diskName(), $this->backupNameFor($type));
    }

    /**
     * Backups for the active tab, newest first, with a human-readable display name.
     *
     * @return array<int, array{path: string, name: string, date: string, size: string}>
     */
    #[Computed]
    public function backups(): array
    {
        $appSlug = str_replace(' ', '_', config('app.name'));
        $typeLabel = $this->backupTab === 'database' ? 'Database' : 'System';

        return $this->destination($this->backupTab)->backups()
            ->map(fn ($backup) => [
                'path' => $backup->path(),
                'name' => "{$appSlug}_{$typeLabel}_Backup_".$backup->date()->format('Y_m_d_H_i').'.zip',
                'date' => $backup->date()->format('d M Y, H:i'),
                'size' => $this->formatBytes($backup->sizeInBytes()),
            ])
            ->values()
            ->all();
    }

    public function generateBackup(): void
    {
        $type = $this->backupTab;
        $label = $type === 'database' ? 'Database' : 'System';
        $options = $type === 'database' ? ['--only-db' => true] : [];

        Config::set('backup.backup.name', $this->backupNameFor($type));

        try {
            Artisan::call('backup:run', $options);
            unset($this->backups);
            $this->showGenerateBackupModal = false;
            Flux::toast(heading: $label.' backup created', text: 'A new backup has been stored.', variant: 'success');
        } catch (\Throwable $e) {
            $this->showGenerateBackupModal = false;
            Flux::toast(heading: 'Backup failed', text: $e->getMessage(), variant: 'danger');
        }
    }

    public function downloadBackup(string $path): ?StreamedResponse
    {
        if (! Storage::disk($this->diskName())->exists($path)) {
            unset($this->backups);
            Flux::toast(heading: 'Not found', text: 'That backup no longer exists.', variant: 'warning');

            return null;
        }

        return Storage::disk($this->diskName())->download($path);
    }

    public function deleteBackup(string $path): void
    {
        $this->destination($this->backupTab)->backups()
            ->first(fn ($backup) => $backup->path() === $path)
            ?->delete();

        unset($this->backups);
        Flux::toast(heading: 'Backup deleted', text: 'The backup file has been removed.', variant: 'success');
    }

    public function clearCache(string $type): void
    {
        $commands = match ($type) {
            'app' => ['cache:clear'],
            'config' => ['config:clear'],
            'route' => ['route:clear'],
            'view' => ['view:clear'],
            'all' => ['cache:clear', 'config:clear', 'route:clear', 'view:clear'],
            default => [],
        };

        if ($commands === []) {
            return;
        }

        foreach ($commands as $command) {
            Artisan::call($command);
        }

        Flux::toast(
            heading: 'Cache cleared',
            text: $type === 'all' ? 'Application, config, route and view caches cleared.' : ucfirst($type).' cache cleared.',
            variant: 'success',
        );

        // Clearing compiled views deletes this component's own compiled template,
        // so redirect for a fresh request rather than re-rendering a missing view.
        $this->redirect(route('admin.settings.other', ['section' => 'cache']));
    }

    // ==================================================
    // BANNED IPs
    // ==================================================

    #[Computed]
    public function bannedIps(): \Illuminate\Database\Eloquent\Collection
    {
        return BannedIp::with('createdBy')->latest()->get();
    }

    public function banIpAddress(): void
    {
        $this->validate([
            'banIp' => ['required', 'ip', 'unique:banned_ips,ip_address'],
            'banComment' => ['nullable', 'string', 'max:255'],
            'banExpiresAt' => ['nullable', 'date', 'after:now'],
        ]);

        $ip = $this->banIp;

        BannedIp::create([
            'ip_address' => $ip,
            'comment' => $this->banComment ?: null,
            'expires_at' => $this->banExpiresAt ?: null,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset('banIp', 'banComment', 'banExpiresAt');
        $this->showBanIpModal = false;
        unset($this->bannedIps);

        Flux::toast(heading: 'IP banned', text: "{$ip} has been blocked.", variant: 'success');
    }

    public function unbanIpAddress(int $id): void
    {
        BannedIp::findOrFail($id)->delete();
        unset($this->bannedIps);

        Flux::toast(heading: 'IP unbanned', text: 'The IP address has been removed from the block list.', variant: 'success');
    }

    public function editBan(int $id): void
    {
        $ban = BannedIp::findOrFail($id);
        $this->editingBanId = $id;
        $this->editComment = $ban->comment ?? '';
        $this->editExpiresAt = $ban->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->showEditBanModal = true;
    }

    public function updateBan(): void
    {
        $this->validate([
            'editComment' => ['nullable', 'string', 'max:255'],
            'editExpiresAt' => ['nullable', 'date', 'after:now'],
        ]);

        BannedIp::findOrFail($this->editingBanId)->update([
            'comment' => $this->editComment ?: null,
            'expires_at' => $this->editExpiresAt ?: null,
        ]);

        $this->showEditBanModal = false;
        $this->editingBanId = null;
        unset($this->bannedIps);

        Flux::toast(heading: 'Ban updated', text: 'The IP ban details have been saved.', variant: 'success');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}; ?>

<x-admin.settings-shell tab="other" :section="$section">

    {{-- Backup --}}
    @if ($section === 'backup')
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Backup</flux:heading>
            </div>

            {{-- Tabs + Generate button --}}
            <div class="flex items-center justify-between gap-4 px-6 py-3">
                <div class="flex gap-2">
                    <flux:button
                        wire:click="switchBackupTab('system')"
                        :variant="$backupTab === 'system' ? 'primary' : 'ghost'"
                        size="sm">
                        System Backup
                    </flux:button>
                    <flux:button
                        wire:click="switchBackupTab('database')"
                        :variant="$backupTab === 'database' ? 'primary' : 'ghost'"
                        size="sm">
                        Database Backup
                    </flux:button>
                </div>
                <flux:button
                    wire:click="$set('showGenerateBackupModal', true)"
                    variant="primary"
                    icon="circle-stack"
                    size="sm">
                    Generate Backup
                </flux:button>
            </div>

            {{-- Table --}}
            <div class="border-t border-zinc-200 dark:border-zinc-700">
                <div class="px-6 py-3">
                    <flux:text size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ $backupTab === 'system' ? 'System' : 'Database' }} Backup List
                    </flux:text>
                </div>

                <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                    <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                        <flux:table.column>File Name</flux:table.column>
                        <flux:table.column class="w-44">Date</flux:table.column>
                        <flux:table.column class="w-28">Size</flux:table.column>
                        <flux:table.column class="w-20"></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->backups as $backup)
                            <flux:table.row wire:key="backup-{{ md5($backup['path']) }}">
                                <flux:table.cell>
                                    <span class="font-mono text-xs font-medium dark:text-white">{{ $backup['name'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm text-brand-500">{{ $backup['date'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="text-sm text-zinc-500">{{ $backup['size'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button size="xs" variant="ghost" icon="arrow-down-tray"
                                            wire:click="downloadBackup('{{ $backup['path'] }}')"
                                            tooltip="Download" />
                                        <flux:button size="xs" variant="ghost" icon="trash-2" class="text-red-500!"
                                            wire:click="deleteBackup('{{ $backup['path'] }}')"
                                            wire:confirm="Delete this backup permanently?"
                                            tooltip="Delete" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">
                                    <div class="py-8 text-center">
                                        <flux:icon.circle-stack class="mx-auto size-7 text-zinc-300 dark:text-zinc-600" />
                                        <flux:text class="mt-2">No backups yet.</flux:text>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>

        {{-- Confirm generate backup modal --}}
        <flux:modal wire:model.self="showGenerateBackupModal" class="w-full max-w-sm" :dismissible="true">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Generate Backup</flux:heading>
                    <flux:subheading>Are you sure you want to generate a {{ $backupTab === 'system' ? 'system' : 'database' }} backup?</flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:button wire:click="generateBackup" wire:loading.attr="disabled" variant="primary" class="flex-1">
                        <span wire:loading.remove wire:target="generateBackup">Yes, generate</span>
                        <span wire:loading wire:target="generateBackup">Generating…</span>
                    </flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Cache --}}
    @if ($section === 'cache')
        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Cache</flux:heading>
            </div>

            <div class="space-y-3 p-6">
                @php
                    $caches = [
                        'app' => ['Application cache', 'Cached data, query results and other app-level cache entries.'],
                        'config' => ['Configuration cache', 'Rebuilds config from files on the next request.'],
                        'route' => ['Route cache', 'Clears the compiled route cache.'],
                        'view' => ['Compiled views', 'Removes compiled Blade templates.'],
                    ];
                @endphp
                @foreach ($caches as $type => [$label, $description])
                    <div class="flex items-center justify-between gap-4 rounded-md border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <div>
                            <flux:label>{{ $label }}</flux:label>
                            <flux:text size="sm" class="text-xs">{{ $description }}</flux:text>
                        </div>
                        <flux:button size="sm" variant="ghost" wire:click="clearCache('{{ $type }}')">Clear</flux:button>
                    </div>
                @endforeach

                <div class="flex justify-end pt-2">
                    <flux:button wire:click="clearCache('all')" variant="primary" icon="bolt">Clear all caches</flux:button>
                </div>
            </div>
        </flux:card>
    @endif


    {{-- Banned IPs --}}
    @if ($section === 'banned-ips')
        <flux:card class="overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:heading size="sm" class="uppercase tracking-wide">Ban IP Address</flux:heading>
                <flux:button wire:click="$set('showBanIpModal', true)" variant="primary" icon="no-symbol" size="sm">
                    Add IP Address
                </flux:button>
            </div>

            @forelse ($this->bannedIps as $ban)
                @if ($loop->first)
                    <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                @endif

                <div @class([
                    'rounded-lg border border-zinc-200 p-4 dark:border-zinc-700',
                    'opacity-50' => $ban->isExpired(),
                ])>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <flux:icon.no-symbol class="size-4 shrink-0 text-zinc-400" />
                            <span class="font-mono text-sm font-semibold dark:text-white">{{ $ban->ip_address }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button size="xs" variant="ghost" icon="pencil-square"
                                wire:click="editBan({{ $ban->id }})" />
                            <flux:button size="xs" variant="ghost" icon="trash-2" class="text-red-500!"
                                wire:click="unbanIpAddress({{ $ban->id }})"
                                wire:confirm="Remove ban for {{ $ban->ip_address }}?" />
                        </div>
                    </div>

                    <flux:separator class="my-3" />

                    @if ($ban->comment)
                        <div class="flex items-start gap-1.5">
                            <flux:icon.information-circle class="mt-0.5 size-3.5 shrink-0 text-zinc-400" />
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ban->comment }}</p>
                        </div>
                    @endif

                    @if ($ban->expires_at)
                        <p class="mt-1.5 text-xs text-zinc-400">
                            {{ $ban->isExpired() ? 'Expired' : 'Expires' }} {{ $ban->expires_at->format('d M Y') }}
                        </p>
                    @endif

                    <p class="mt-1.5 text-xs text-zinc-400">
                        Banned by {{ $ban->createdBy?->name ?? 'System' }} · {{ $ban->created_at->format('d M Y') }}
                    </p>
                </div>

                @if ($loop->last)
                    </div>
                @endif
            @empty
                <div class="px-4 py-12 text-center">
                    <flux:icon.no-symbol class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <flux:text class="mt-2">No IP addresses are currently banned.</flux:text>
                </div>
            @endforelse
        </flux:card>

        {{-- Add IP modal --}}
        <flux:modal wire:model.self="showBanIpModal" class="w-full max-w-md" :dismissible="true">
            <form wire:submit="banIpAddress" class="space-y-5">
                <div>
                    <flux:heading size="lg">Ban IP Address</flux:heading>
                    <flux:subheading>The IP will be blocked from all storefront requests.</flux:subheading>
                </div>

                <flux:input wire:model="banIp" label="IP address" placeholder="e.g. 192.168.1.100" autofocus />
                <flux:input wire:model="banComment" label="Reason" placeholder="e.g. Spam, abuse, brute-force…" />
                <flux:input wire:model="banExpiresAt" type="datetime-local" label="Expires at (optional)"
                    description="Leave empty for a permanent ban." />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" class="flex-1" icon="no-symbol">Ban IP</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>

        {{-- Edit IP modal --}}
        <flux:modal wire:model.self="showEditBanModal" class="w-full max-w-md" :dismissible="true">
            <form wire:submit="updateBan" class="space-y-5">
                <div>
                    <flux:heading size="lg">Edit Ban</flux:heading>
                    <flux:subheading>Update the reason or expiry for this IP ban.</flux:subheading>
                </div>

                <flux:input wire:model="editComment" label="Reason" placeholder="e.g. Spam, abuse, brute-force…" />
                <flux:input wire:model="editExpiresAt" type="datetime-local" label="Expires at (optional)"
                    description="Leave empty for a permanent ban." />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" class="flex-1">Save changes</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>
    @endif

</x-admin.settings-shell>
