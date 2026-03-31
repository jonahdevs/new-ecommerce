<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupManagement extends Command
{
    protected $signature = 'backup:manage 
                           {action : The action to perform (create|list|stats|cleanup|monitor|test)}
                           {--type=full : Type of backup (full|database|files)}
                           {--disk= : Specific disk to operate on}';

    protected $description = 'Manage application backups dynamically';

    public function handle(BackupService $backupService): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'create':
                return $this->createBackup($backupService);
            case 'list':
                return $this->listBackups($backupService);
            case 'stats':
                return $this->showStats($backupService);
            case 'cleanup':
                return $this->cleanupBackups($backupService);
            case 'monitor':
                return $this->monitorBackups($backupService);
            case 'test':
                return $this->testConfiguration($backupService);
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    private function createBackup(BackupService $backupService): int
    {
        $type = $this->option('type');

        $this->info("Creating {$type} backup...");

        $result = match ($type) {
            'database' => $backupService->createDatabaseBackup(),
            'files' => $backupService->createFilesBackup(),
            default => $backupService->createFullBackup(),
        };

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        } else {
            $this->error($result['message']);
            return 1;
        }
    }
    private function listBackups(BackupService $backupService): int
    {
        $backups = $backupService->listBackups();

        if ($backups->isEmpty()) {
            $this->info('No backups found.');
            return 0;
        }

        $this->table(
            ['Disk', 'Filename', 'Size', 'Created', 'Age (days)'],
            $backups->map(function ($backup) {
                return [
                    $backup['disk'],
                    $backup['filename'],
                    $backup['size'],
                    $backup['created_at']->format('Y-m-d H:i:s'),
                    $backup['age_days'],
                ];
            })->toArray()
        );

        return 0;
    }

    private function showStats(BackupService $backupService): int
    {
        $stats = $backupService->getBackupStats();

        $this->info('Backup Statistics:');
        $this->line("Total backups: {$stats['total_backups']}");
        $this->line("Total size: {$stats['total_size']}");

        if ($stats['oldest_backup']) {
            $this->line("Oldest backup: {$stats['oldest_backup']->format('Y-m-d H:i:s')}");
        }

        if ($stats['newest_backup']) {
            $this->line("Newest backup: {$stats['newest_backup']->format('Y-m-d H:i:s')}");
        }

        $this->newLine();
        $this->info('By Disk:');

        foreach ($stats['by_disk'] as $disk => $diskStats) {
            $this->line("  {$disk}: {$diskStats['count']} backups, {$diskStats['size']}");
        }

        return 0;
    }

    private function cleanupBackups(BackupService $backupService): int
    {
        $this->info('Cleaning up old backups...');

        $result = $backupService->cleanupBackups();

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        } else {
            $this->error($result['message']);
            return 1;
        }
    }

    private function monitorBackups(BackupService $backupService): int
    {
        $this->info('Monitoring backup health...');

        $result = $backupService->monitorBackups();

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        } else {
            $this->error($result['message']);
            return 1;
        }
    }

    private function testConfiguration(BackupService $backupService): int
    {
        $this->info('Testing backup configuration...');

        $results = $backupService->testConfiguration();

        // Test database
        if ($results['database']['status'] === 'ok') {
            $this->info('✓ ' . $results['database']['message']);
        } else {
            $this->error('✗ ' . $results['database']['message']);
        }

        // Test disks
        foreach ($results['disks'] as $disk => $result) {
            if ($result['status'] === 'ok') {
                $this->info("✓ Disk '{$disk}': " . $result['message']);
            } else {
                $this->error("✗ Disk '{$disk}': " . $result['message']);
            }
        }

        return 0;
    }
}