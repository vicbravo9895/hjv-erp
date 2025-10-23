<?php

namespace App\Services;

use App\Models\SamsaraSyncLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SamsaraSyncLogService
{
    /**
     * Start a new sync log entry
     *
     * @param string $syncType Type of sync (vehicles, trailers, drivers)
     * @param array $params Additional parameters for the sync
     * @return SamsaraSyncLog
     */
    public function startSync(string $syncType, array $params = []): SamsaraSyncLog
    {
        $syncLog = SamsaraSyncLog::create([
            'sync_type' => $syncType,
            'status' => 'running',
            'started_at' => now(),
            'params' => $params,
        ]);

        if (config('samsara.logging.log_sync_details')) {
            Log::channel(config('samsara.logging.channel'))
                ->info("Started Samsara sync", [
                    'sync_id' => $syncLog->id,
                    'sync_type' => $syncType,
                    'params' => $params,
                ]);
        }

        return $syncLog;
    }

    /**
     * Complete a sync log entry with success
     *
     * @param SamsaraSyncLog $syncLog
     * @param int $syncedRecords Number of records synchronized
     * @param array $additionalData Additional data to log
     * @return SamsaraSyncLog
     */
    public function completeSync(SamsaraSyncLog $syncLog, int $syncedRecords, array $additionalData = []): SamsaraSyncLog
    {
        $endTime = now();
        $durationSeconds = (int) $endTime->diffInSeconds($syncLog->started_at);

        $syncLog->update([
            'status' => 'completed',
            'synced_records' => $syncedRecords,
            'duration_seconds' => $durationSeconds,
            'completed_at' => $endTime,
            'additional_data' => $additionalData,
        ]);

        if (config('samsara.logging.log_sync_details')) {
            Log::channel(config('samsara.logging.channel'))
                ->info("Completed Samsara sync", [
                    'sync_id' => $syncLog->id,
                    'sync_type' => $syncLog->sync_type,
                    'synced_records' => $syncedRecords,
                    'duration_seconds' => $durationSeconds,
                    'additional_data' => $additionalData,
                ]);
        }

        return $syncLog;
    }

    /**
     * Mark a sync log entry as failed
     *
     * @param SamsaraSyncLog $syncLog
     * @param string $errorMessage Error message
     * @param array $errorDetails Additional error details
     * @return SamsaraSyncLog
     */
    public function failSync(SamsaraSyncLog $syncLog, string $errorMessage, array $errorDetails = []): SamsaraSyncLog
    {
        $endTime = now();
        $durationSeconds = (int) $endTime->diffInSeconds($syncLog->started_at);

        $syncLog->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'duration_seconds' => $durationSeconds,
            'completed_at' => $endTime,
            'error_details' => $errorDetails,
        ]);

        Log::channel(config('samsara.logging.channel'))
            ->error("Failed Samsara sync", [
                'sync_id' => $syncLog->id,
                'sync_type' => $syncLog->sync_type,
                'error_message' => $errorMessage,
                'duration_seconds' => $durationSeconds,
                'error_details' => $errorDetails,
            ]);

        return $syncLog;
    }

    /**
     * Get sync statistics for a given period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $syncType
     * @return array
     */
    public function getSyncStats(Carbon $startDate, Carbon $endDate, string $syncType = null): array
    {
        $query = SamsaraSyncLog::whereBetween('started_at', [$startDate, $endDate]);

        if ($syncType) {
            $query->where('sync_type', $syncType);
        }

        $logs = $query->get();

        return [
            'total_syncs' => $logs->count(),
            'successful_syncs' => $logs->where('status', 'completed')->count(),
            'failed_syncs' => $logs->where('status', 'failed')->count(),
            'running_syncs' => $logs->where('status', 'running')->count(),
            'total_records_synced' => $logs->where('status', 'completed')->sum('synced_records'),
            'average_duration' => $logs->where('status', 'completed')->avg('duration_seconds'),
            'by_type' => $logs->groupBy('sync_type')->map(function ($typeLogs) {
                return [
                    'count' => $typeLogs->count(),
                    'successful' => $typeLogs->where('status', 'completed')->count(),
                    'failed' => $typeLogs->where('status', 'failed')->count(),
                    'records_synced' => $typeLogs->where('status', 'completed')->sum('synced_records'),
                ];
            }),
        ];
    }

    /**
     * Get recent sync logs
     *
     * @param int $limit
     * @param string|null $syncType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentSyncs(int $limit = 50, string $syncType = null)
    {
        $query = SamsaraSyncLog::orderBy('started_at', 'desc')->limit($limit);

        if ($syncType) {
            $query->where('sync_type', $syncType);
        }

        return $query->get();
    }

    /**
     * Clean up old sync logs
     *
     * @param int $daysToKeep
     * @return int Number of deleted records
     */
    public function cleanupOldLogs(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deletedCount = SamsaraSyncLog::where('started_at', '<', $cutoffDate)->delete();

        if ($deletedCount > 0) {
            Log::channel(config('samsara.logging.channel'))
                ->info("Cleaned up old Samsara sync logs", [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                ]);
        }

        return $deletedCount;
    }

    /**
     * Check if sync should run based on configuration
     *
     * @param string $syncType
     * @return bool
     */
    public function shouldRunSync(string $syncType): bool
    {
        $now = now();

        // Check if sync is enabled for this type
        $enabledKey = "samsara.sync.enable_{$syncType}_sync";
        if (!config($enabledKey, true)) {
            return false;
        }

        // Check operating hours
        $startHour = config('samsara.sync.operating_hours.start', 6);
        $endHour = config('samsara.sync.operating_hours.end', 22);
        
        if ($now->hour < $startHour || $now->hour >= $endHour) {
            return false;
        }

        // Check weekdays only setting
        if (config('samsara.sync.weekdays_only', false) && $now->isWeekend()) {
            return false;
        }

        // Check if there's already a running sync of this type
        $runningSyncs = SamsaraSyncLog::where('sync_type', $syncType)
            ->where('status', 'running')
            ->where('started_at', '>', now()->subMinutes(30)) // Consider stuck after 30 minutes
            ->count();

        return $runningSyncs === 0;
    }

    /**
     * Mark stuck syncs as failed
     *
     * @param int $timeoutMinutes
     * @return int Number of syncs marked as failed
     */
    public function markStuckSyncsAsFailed(int $timeoutMinutes = 30): int
    {
        $cutoffTime = now()->subMinutes($timeoutMinutes);
        
        $stuckSyncs = SamsaraSyncLog::where('status', 'running')
            ->where('started_at', '<', $cutoffTime)
            ->get();

        foreach ($stuckSyncs as $sync) {
            $this->failSync($sync, 'Sync timeout - marked as failed by cleanup process');
        }

        return $stuckSyncs->count();
    }
}