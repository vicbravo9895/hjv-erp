<?php

namespace App\Console\Commands;

use App\Services\AttachmentService;
use Illuminate\Console\Command;

class CleanupOrphanedAttachments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attachments:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--older-than=24 : Delete files older than specified hours (default: 24)}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up orphaned attachment files and temporary uploads';

    /**
     * Execute the console command.
     */
    public function handle(AttachmentService $attachmentService): int
    {
        $dryRun = $this->option('dry-run');
        $olderThan = (int) $this->option('older-than');

        $this->info('Starting attachment cleanup...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
        }

        // Clean up orphaned attachment files
        $this->info('Cleaning up orphaned attachment files...');
        
        if (!$dryRun) {
            $cleaned = $attachmentService->cleanupOrphanedFiles();
            $this->info("Cleaned up {$cleaned} orphaned attachment files.");
        } else {
            $this->info('Would clean up orphaned attachment files (dry run).');
        }

        // Clean up old temporary uploads
        $this->info("Cleaning up temporary uploads older than {$olderThan} hours...");
        $tempCleaned = $this->cleanupTempUploads($olderThan, $dryRun);
        
        if (!$dryRun) {
            $this->info("Cleaned up {$tempCleaned} temporary upload files.");
        } else {
            $this->info("Would clean up {$tempCleaned} temporary upload files (dry run).");
        }

        $this->info('Attachment cleanup completed.');
        
        return Command::SUCCESS;
    }

    /**
     * Clean up old temporary upload files.
     */
    protected function cleanupTempUploads(int $olderThanHours, bool $dryRun = false): int
    {
        $cleaned = 0;
        $cutoffTime = now()->subHours($olderThanHours);

        try {
            $tempFiles = \Storage::disk('minio')->allFiles('temp-uploads');

            foreach ($tempFiles as $filePath) {
                $lastModified = \Storage::disk('minio')->lastModified($filePath);
                
                if ($lastModified && $lastModified < $cutoffTime->timestamp) {
                    if (!$dryRun) {
                        \Storage::disk('minio')->delete($filePath);
                    }
                    $cleaned++;
                    
                    $this->line("Cleaned: {$filePath}");
                }
            }

        } catch (\Exception $e) {
            $this->error("Error cleaning temp uploads: {$e->getMessage()}");
        }

        return $cleaned;
    }
}