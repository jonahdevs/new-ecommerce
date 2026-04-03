<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDashboard extends Command
{
    protected $signature = 'backup:dashboard';

    protected $description = 'Display backup management dashboard';

    public function handle(BackupService $backupService): int
    {
        $this->displayHeader();
        $this->displayStats($backupService);
        $this->displayRecentBackups($backupService);
        $this->displaySchedule();
        $this->displayCommands();

        return 0;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    Sheffield Backup Dashboard                ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function displayStats(BackupService $backupService): void
    {
        $stats = $backupService->getBackupStats();

        $this->info('📊 Backup Statistics:');
        $this->line("   Total backups: {$stats['total_backups']}");
        $this->line("   Total size: {$stats['total_size']}");

        if ($stats['newest_backup']) {
            $this->line("   Latest backup: {$stats['newest_backup']->format('Y-m-d H:i:s')}");
        }

        $this->newLine();
    }

    private function displayRecentBackups(BackupService $backupService): void
    {
        $backups = $backupService->listBackups()->take(5);

        if ($backups->isNotEmpty()) {
            $this->info('📁 Recent Backups:');
            $this->table(
                ['Disk', 'Filename', 'Size', 'Created'],
                $backups->map(function ($backup) {
                    return [
                        $backup['disk'],
                        substr($backup['filename'], 0, 40).(strlen($backup['filename']) > 40 ? '...' : ''),
                        $backup['size'],
                        $backup['created_at']->format('M j, H:i'),
                    ];
                })->toArray()
            );
        } else {
            $this->warn('No backups found.');
        }

        $this->newLine();
    }

    private function displaySchedule(): void
    {
        $this->info('⏰ Backup Schedule:');
        $this->line('   • Daily database backup: 02:00 AM');
        $this->line('   • Weekly full backup: Sunday 03:00 AM');
        $this->line('   • Monthly cleanup: 1st day 04:00 AM');
        $this->line('   • Daily health check: 06:00 AM');
        $this->newLine();
    }

    private function displayCommands(): void
    {
        $this->info('🛠️  Available Commands:');
        $this->line('   php artisan backup:manage create --type=full    # Create full backup');
        $this->line('   php artisan backup:manage create --type=database # Database only');
        $this->line('   php artisan backup:manage list                   # List all backups');
        $this->line('   php artisan backup:manage stats                  # Show statistics');
        $this->line('   php artisan backup:manage cleanup                # Clean old backups');
        $this->line('   php artisan backup:manage monitor                # Check health');
        $this->line('   php artisan backup:manage test                   # Test configuration');
        $this->newLine();
    }
}
