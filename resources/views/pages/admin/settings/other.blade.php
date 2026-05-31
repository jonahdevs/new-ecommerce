<?php

use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts::app')] #[Title('Maintenance — Admin')] class extends Component
{
    #[Url]
    public string $section = 'backup';

    private function diskName(): string
    {
        return config('backup.backup.destination.disks')[0] ?? 'local';
    }

    private function destination(): BackupDestination
    {
        return BackupDestination::create($this->diskName(), config('backup.backup.name'));
    }

    /**
     * Existing backups, newest first.
     *
     * @return array<int, array{path: string, filename: string, date: string, size: string}>
     */
    #[Computed]
    public function backups(): array
    {
        return $this->destination()->backups()
            ->map(fn ($backup) => [
                'path' => $backup->path(),
                'filename' => basename($backup->path()),
                'date' => $backup->date()->diffForHumans(),
                'size' => $this->formatBytes($backup->sizeInBytes()),
            ])
            ->values()
            ->all();
    }

    public function backupDatabase(): void
    {
        $this->runBackup(['--only-db' => true]);
    }

    public function backupFull(): void
    {
        $this->runBackup([]);
    }

    /** @param array<string, mixed> $options */
    private function runBackup(array $options): void
    {
        try {
            Artisan::call('backup:run', $options);
            unset($this->backups);
            Flux::toast(heading: 'Backup created', text: 'A new backup has been stored.', variant: 'success');
        } catch (\Throwable $e) {
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
        $this->destination()->backups()
            ->first(fn ($backup) => $backup->path() === $path)
            ?->delete();

        unset($this->backups);
        Flux::toast(heading: 'Backup deleted', variant: 'success');
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
        <flux:card>
            <flux:heading>Backup</flux:heading>
            <flux:subheading>Create and download backups of your database and files.</flux:subheading>

            <div class="mt-6 flex flex-wrap gap-3">
                <flux:button wire:click="backupDatabase" wire:loading.attr="disabled" variant="primary" icon="circle-stack">
                    Back up database
                </flux:button>
                <flux:button wire:click="backupFull" wire:loading.attr="disabled" variant="filled" icon="archive-box">
                    Full backup (files + database)
                </flux:button>
            </div>

            <div wire:loading wire:target="backupDatabase,backupFull" class="mt-3">
                <flux:text size="sm" class="text-zinc-500">Creating backup — this can take a moment…</flux:text>
            </div>

            <flux:separator class="my-6" />

            <flux:heading size="sm">Available backups</flux:heading>
            <div class="mt-3 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-700">
                @forelse ($this->backups as $backup)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 @if (! $loop->last) border-b border-zinc-100 dark:border-zinc-800 @endif">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium dark:text-white">{{ $backup['filename'] }}</p>
                            <p class="text-xs text-zinc-500">{{ $backup['date'] }} · {{ $backup['size'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray"
                                wire:click="downloadBackup('{{ $backup['path'] }}')">Download</flux:button>
                            <flux:button size="xs" variant="ghost" icon="trash" class="text-red-500!"
                                wire:click="deleteBackup('{{ $backup['path'] }}')"
                                wire:confirm="Delete this backup permanently?" />
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon.circle-stack class="mx-auto size-7 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="mt-2">No backups yet.</flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>
    @endif

    {{-- Cache --}}
    @if ($section === 'cache')
        <flux:card>
            <flux:heading>Cache</flux:heading>
            <flux:subheading>Clear cached data after configuration or deployment changes.</flux:subheading>

            <div class="mt-6 space-y-3">
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

</x-admin.settings-shell>
