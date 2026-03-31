<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledBackup extends Command
{
    protected $signature = 'backup:scheduled 
                           {type : Type of backup (database|files|full)}
                           {--notify : Send notification on completion}';

    protected $description = 'Run scheduled backups with enhanced logging and notifications';

    public function handle(BackupService $backupService): int
    {
        $type = $this->argument('type');
        $notify = $this->option('notify');

        $this->info("Starting scheduled {$type} backup...");
        Log::info("Scheduled {$type} backup initiated via command");

        $result = match ($type) {
            'database' => $backupService->createDatabaseBackup(),
            'files' => $backupService->createFilesBackup(),
            'full' => $backupService->createFullBackup(),
            default => ['success' => false, 'message' => 'Invalid backup type'],
        };

        if ($result['success']) {
            $this->info($result['message']);
            Log::info("Scheduled {$type} backup completed successfully", $result);

            if ($notify) {
                $this->sendNotification($type, $result, true);
            }

            return 0;
        } else {
            $this->error($result['message']);
            Log::error("Scheduled {$type} backup failed", $result);

            if ($notify) {
                $this->sendNotification($type, $result, false);
            }

            return 1;
        }
    }

    private function sendNotification(string $type, array $result, bool $success): void
    {
        try {
            // You can customize this to send notifications via your preferred method
            // For now, we'll just log it
            $status = $success ? 'SUCCESS' : 'FAILED';
            $message = "Backup {$status}: {$type} backup - {$result['message']}";

            Log::info("Backup notification: {$message}");

            // Example: Send email notification
            // Mail::to(config('backup.notifications.mail.to'))
            //     ->send(new BackupNotification($type, $result, $success));

        } catch (\Exception $e) {
            Log::error('Failed to send backup notification: ' . $e->getMessage());
        }
    }
}