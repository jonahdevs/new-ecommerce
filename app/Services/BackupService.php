<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Service for managing application backups dynamically
 */
class BackupService
{
    /**
     * Create a full backup (database + files)
     */
    public function createFullBackup(array $options = []): array
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting full backup process');

            // Run the backup command
            $exitCode = Artisan::call('backup:run', array_merge([
                '--only-db' => false,
                '--only-files' => false,
            ], $options));

            $duration = round(microtime(true) - $startTime, 2);

            if ($exitCode === 0) {
                Log::info("Full backup completed successfully in {$duration} seconds");
                return [
                    'success' => true,
                    'message' => "Full backup completed in {$duration} seconds",
                    'duration' => $duration,
                    'type' => 'full'
                ];
            } else {
                throw new \Exception('Backup command failed with exit code: ' . $exitCode);
            }
        } catch (\Exception $e) {
            Log::error('Full backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
                'type' => 'full'
            ];
        }
    }
    /**
     * Create a database-only backup
     */
    public function createDatabaseBackup(): array
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting database backup');

            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            if ($exitCode === 0) {
                Log::info("Database backup completed in {$duration} seconds");
                return [
                    'success' => true,
                    'message' => "Database backup completed in {$duration} seconds",
                    'duration' => $duration,
                    'type' => 'database'
                ];
            } else {
                throw new \Exception('Database backup failed with exit code: ' . $exitCode);
            }
        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database backup failed: ' . $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
                'type' => 'database'
            ];
        }
    }

    /**
     * Create a files-only backup
     */
    public function createFilesBackup(): array
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting files backup');

            $exitCode = Artisan::call('backup:run', [
                '--only-files' => true,
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            if ($exitCode === 0) {
                Log::info("Files backup completed in {$duration} seconds");
                return [
                    'success' => true,
                    'message' => "Files backup completed in {$duration} seconds",
                    'duration' => $duration,
                    'type' => 'files'
                ];
            } else {
                throw new \Exception('Files backup failed with exit code: ' . $exitCode);
            }
        } catch (\Exception $e) {
            Log::error('Files backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Files backup failed: ' . $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
                'type' => 'files'
            ];
        }
    }
    /**
     * Get list of all backups across all configured disks
     */
    public function listBackups(): Collection
    {
        $backups = collect();
        $disks = config('backup.backup.destination.disks', ['local']);

        foreach ($disks as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                $appName = config('backup.backup.name');

                if ($disk->exists($appName)) {
                    $files = $disk->files($appName);

                    foreach ($files as $file) {
                        if (str_ends_with($file, '.zip')) {
                            $backups->push([
                                'disk' => $diskName,
                                'path' => $file,
                                'filename' => basename($file),
                                'size' => $this->formatBytes($disk->size($file)),
                                'size_bytes' => $disk->size($file),
                                'created_at' => Carbon::createFromTimestamp($disk->lastModified($file)),
                                'age_days' => Carbon::createFromTimestamp($disk->lastModified($file))->diffInDays(now()),
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Could not list backups from disk {$diskName}: " . $e->getMessage());
            }
        }

        return $backups->sortByDesc('created_at');
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats(): array
    {
        $backups = $this->listBackups();

        return [
            'total_backups' => $backups->count(),
            'total_size' => $this->formatBytes($backups->sum('size_bytes')),
            'total_size_bytes' => $backups->sum('size_bytes'),
            'oldest_backup' => $backups->min('created_at'),
            'newest_backup' => $backups->max('created_at'),
            'by_disk' => $backups->groupBy('disk')->map(function ($diskBackups) {
                return [
                    'count' => $diskBackups->count(),
                    'size' => $this->formatBytes($diskBackups->sum('size_bytes')),
                    'size_bytes' => $diskBackups->sum('size_bytes'),
                ];
            }),
        ];
    }
    /**
     * Clean up old backups
     */
    public function cleanupBackups(): array
    {
        try {
            Log::info('Starting backup cleanup');

            $exitCode = Artisan::call('backup:clean');

            if ($exitCode === 0) {
                Log::info('Backup cleanup completed successfully');
                return [
                    'success' => true,
                    'message' => 'Backup cleanup completed successfully'
                ];
            } else {
                throw new \Exception('Cleanup command failed with exit code: ' . $exitCode);
            }
        } catch (\Exception $e) {
            Log::error('Backup cleanup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Monitor backup health
     */
    public function monitorBackups(): array
    {
        try {
            Log::info('Monitoring backup health');

            $exitCode = Artisan::call('backup:monitor');

            if ($exitCode === 0) {
                return [
                    'success' => true,
                    'message' => 'All backups are healthy'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Some backups are unhealthy - check logs for details'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Backup monitoring failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Monitoring failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a specific backup
     */
    public function deleteBackup(string $disk, string $path): array
    {
        try {
            $storage = Storage::disk($disk);

            if (!$storage->exists($path)) {
                return [
                    'success' => false,
                    'message' => 'Backup file not found'
                ];
            }

            $storage->delete($path);

            Log::info("Backup deleted: {$path} from disk {$disk}");

            return [
                'success' => true,
                'message' => 'Backup deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Failed to delete backup {$path}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Schedule automatic backups based on configuration
     */
    public function scheduleBackups(): array
    {
        $schedule = [];

        // Daily database backup at 2 AM
        $schedule[] = [
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'command' => 'backup:run --only-db'
        ];

        // Weekly full backup on Sunday at 3 AM
        $schedule[] = [
            'type' => 'full',
            'frequency' => 'weekly',
            'day' => 'sunday',
            'time' => '03:00',
            'command' => 'backup:run'
        ];

        // Monthly cleanup on first day at 4 AM
        $schedule[] = [
            'type' => 'cleanup',
            'frequency' => 'monthly',
            'day' => 1,
            'time' => '04:00',
            'command' => 'backup:clean'
        ];

        return $schedule;
    }

    /**
     * Test backup configuration
     */
    public function testConfiguration(): array
    {
        $results = [];

        // Test database connection
        try {
            \DB::connection()->getPdo();
            $results['database'] = ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            $results['database'] = ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }

        // Test storage disks
        $disks = config('backup.backup.destination.disks', ['local']);
        foreach ($disks as $disk) {
            try {
                $storage = Storage::disk($disk);
                $testFile = 'backup-test-' . time() . '.txt';
                $storage->put($testFile, 'test');
                $storage->delete($testFile);
                $results['disks'][$disk] = ['status' => 'ok', 'message' => 'Disk accessible'];
            } catch (\Exception $e) {
                $results['disks'][$disk] = ['status' => 'error', 'message' => 'Disk error: ' . $e->getMessage()];
            }
        }

        return $results;
    }
}