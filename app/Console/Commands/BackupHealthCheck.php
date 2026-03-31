<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupHealthCheck extends Command
{
    protected $signature = 'backup:health-check {--alert : Send alerts for issues}';
    protected $description = 'Comprehensive backup health check with alerting';

    public function handle(BackupService $backupService): int
    {
        $this->info('Running comprehensive backup health check...');

        $issues = [];
        $warnings = [];

        // Check recent backups
        $backups = $backupService->listBackups();

        if ($backups->isEmpty()) {
            $issues[] = 'No backups found in the system';
        } else {
            $latestBackup = $backups->first();
            $hoursSinceLastBackup = $latestBackup['created_at']->diffInHours(now());

            if ($hoursSinceLastBackup > 48) {
                $issues[] = "Latest backup is {$hoursSinceLastBackup} hours old (older than 48 hours)";
            } elseif ($hoursSinceLastBackup > 24) {
                $warnings[] = "Latest backup is {$hoursSinceLastBackup} hours old";
            }
        }

        // Check backup sizes
        $stats = $backupService->getBackupStats();
        if ($stats['total_size_bytes'] > 10 * 1024 * 1024 * 1024) { // 10GB
            $warnings[] = "Total backup size is large: {$stats['total_size']}";
        }

        // Check disk space
        $this->checkDiskSpace($issues, $warnings);

        // Test configuration
        $configResults = $backupService->testConfiguration();
        foreach ($configResults['disks'] ?? [] as $disk => $result) {
            if ($result['status'] !== 'ok') {
                $issues[] = "Disk '{$disk}' has issues: {$result['message']}";
            }
        }

        if ($configResults['database']['status'] !== 'ok') {
            $issues[] = "Database connection issue: {$configResults['database']['message']}";
        }

        // Display results
        $this->displayResults($issues, $warnings);

        // Send alerts if requested
        if ($this->option('alert') && (!empty($issues) || !empty($warnings))) {
            $this->sendHealthAlert($issues, $warnings);
        }

        return empty($issues) ? 0 : 1;
    }

    private function checkDiskSpace(array &$issues, array &$warnings): void
    {
        $disks = config('backup.backup.destination.disks', ['local']);

        foreach ($disks as $diskName) {
            try {
                $disk = \Storage::disk($diskName);
                $path = $disk->path('');

                if (function_exists('disk_free_space')) {
                    $freeBytes = disk_free_space($path);
                    $totalBytes = disk_total_space($path);

                    if ($freeBytes && $totalBytes) {
                        $freePercent = ($freeBytes / $totalBytes) * 100;

                        if ($freePercent < 5) {
                            $issues[] = "Disk '{$diskName}' has less than 5% free space";
                        } elseif ($freePercent < 15) {
                            $warnings[] = "Disk '{$diskName}' has less than 15% free space";
                        }
                    }
                }
            } catch (\Exception $e) {
                $warnings[] = "Could not check disk space for '{$diskName}': {$e->getMessage()}";
            }
        }
    }

    private function displayResults(array $issues, array $warnings): void
    {
        if (empty($issues) && empty($warnings)) {
            $this->info('✅ All backup health checks passed!');
            return;
        }

        if (!empty($issues)) {
            $this->error('❌ Critical Issues Found:');
            foreach ($issues as $issue) {
                $this->line("   • {$issue}");
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->line("   • {$warning}");
            }
            $this->newLine();
        }
    }

    private function sendHealthAlert(array $issues, array $warnings): void
    {
        $message = "Backup Health Check Alert\n\n";

        if (!empty($issues)) {
            $message .= "Critical Issues:\n";
            foreach ($issues as $issue) {
                $message .= "• {$issue}\n";
            }
            $message .= "\n";
        }

        if (!empty($warnings)) {
            $message .= "Warnings:\n";
            foreach ($warnings as $warning) {
                $message .= "• {$warning}\n";
            }
        }

        Log::warning('Backup health check found issues', [
            'issues' => $issues,
            'warnings' => $warnings
        ]);

        // You can extend this to send actual notifications
        $this->info('Health alert logged. Configure notification channels as needed.');
    }
}