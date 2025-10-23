<?php

namespace App\Console\Commands;

use App\Services\SamsaraSyncLogService;
use Illuminate\Console\Command;

class CleanupSamsaraSyncLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samsara:cleanup-logs 
                            {--days=30 : Number of days to keep logs}
                            {--mark-stuck : Mark stuck syncs as failed}
                            {--timeout=30 : Timeout in minutes for stuck syncs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old Samsara sync logs and mark stuck syncs as failed';

    protected SamsaraSyncLogService $syncLogService;

    public function __construct(SamsaraSyncLogService $syncLogService)
    {
        parent::__construct();
        $this->syncLogService = $syncLogService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysToKeep = (int) $this->option('days');
        $timeoutMinutes = (int) $this->option('timeout');

        $this->info("Starting Samsara sync logs cleanup...");

        // Mark stuck syncs as failed if requested
        if ($this->option('mark-stuck')) {
            $stuckCount = $this->syncLogService->markStuckSyncsAsFailed($timeoutMinutes);
            if ($stuckCount > 0) {
                $this->info("Marked {$stuckCount} stuck syncs as failed");
            }
        }

        // Clean up old logs
        $deletedCount = $this->syncLogService->cleanupOldLogs($daysToKeep);
        
        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} old sync logs (older than {$daysToKeep} days)");
        } else {
            $this->info("No old logs to clean up");
        }

        $this->info("Cleanup completed successfully");

        return self::SUCCESS;
    }
}