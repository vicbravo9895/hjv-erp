<?php

namespace App\Console\Commands;

use App\Models\Trailer;
use App\Services\SamsaraClient;
use App\Services\SamsaraSyncLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncTrailers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samsara:sync-trailers 
                            {--limit=100 : Number of records per page}
                            {--tag-ids= : Comma-separated tag IDs to filter trailers}
                            {--force : Force sync even outside operating hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize trailers data from Samsara API';

    protected SamsaraClient $samsaraClient;
    protected SamsaraSyncLogService $syncLogService;

    public function __construct(SamsaraClient $samsaraClient, SamsaraSyncLogService $syncLogService)
    {
        parent::__construct();
        $this->samsaraClient = $samsaraClient;
        $this->syncLogService = $syncLogService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if sync should run
        if (!$this->option('force') && !$this->syncLogService->shouldRunSync('trailers')) {
            $this->info('Trailer sync is disabled or outside operating hours');
            return self::SUCCESS;
        }

        $startTime = now();
        $limit = (int) $this->option('limit');
        $tagIds = $this->option('tag-ids') ? explode(',', $this->option('tag-ids')) : null;

        // Start sync logging
        $syncLog = $this->syncLogService->startSync('trailers', [
            'limit' => $limit,
            'tag_ids' => $tagIds,
            'forced' => $this->option('force'),
        ]);

        $this->info("Starting trailer synchronization...");

        try {
            $syncedCount = 0;
            $errorCount = 0;

            // Iterate through all trailers from Samsara
            $stats = $this->samsaraClient->iterateTrailers(
                function ($trailerData) use (&$syncedCount, &$errorCount) {
                    try {
                        $this->processTrailer($trailerData);
                        $syncedCount++;
                        
                        if ($syncedCount % 50 === 0) {
                            $this->info("Processed {$syncedCount} trailers...");
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('Error processing trailer', [
                            'trailer_id' => $trailerData['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                },
                $limit,
                $tagIds
            );

            // Mark trailers as inactive if they weren't updated in this sync
            $inactiveCount = $this->markInactiveTrailers($startTime);

            // Complete sync logging
            $this->syncLogService->completeSync($syncLog, $syncedCount, [
                'errors' => $errorCount,
                'inactive_marked' => $inactiveCount,
                'api_stats' => $stats,
            ]);

            $this->info("Trailer synchronization completed!");
            $this->info("- Synced: {$syncedCount} trailers");
            $this->info("- Errors: {$errorCount}");
            $this->info("- Marked inactive: {$inactiveCount}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->syncLogService->failSync($syncLog, $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->error("Trailer synchronization failed: " . $e->getMessage());
            Log::error('Trailer sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process individual trailer data from Samsara
     */
    protected function processTrailer(array $trailerData): void
    {
        $externalId = $trailerData['id'];
        
        // Extract basic trailer information
        $trailerInfo = [
            'external_id' => $externalId,
            'name' => $trailerData['name'] ?? null,
            'synced_at' => now(),
            'raw_snapshot' => $trailerData,
        ];

        // Extract asset number from name if available
        if (!empty($trailerData['name'])) {
            $trailerInfo['asset_number'] = $this->extractAssetNumber($trailerData['name']);
        }

        // Process GPS data directly from trailer data
        if (isset($trailerData['gps']) && !empty($trailerData['gps'])) {
            $gpsData = end($trailerData['gps']); // Get latest GPS data
            $trailerInfo['last_lat'] = $gpsData['latitude'] ?? null;
            $trailerInfo['last_lng'] = $gpsData['longitude'] ?? null;
            $trailerInfo['last_speed_mph'] = $gpsData['speedMilesPerHour'] ?? null;
            $trailerInfo['last_heading_degrees'] = $gpsData['headingDegrees'] ?? null;
            $trailerInfo['last_location_at'] = isset($gpsData['time']) ? 
                Carbon::parse($gpsData['time']) : null;
            
            // Format location if coordinates are available
            if ($trailerInfo['last_lat'] && $trailerInfo['last_lng']) {
                $trailerInfo['formatted_location'] = 
                    number_format($trailerInfo['last_lat'], 6) . ', ' . 
                    number_format($trailerInfo['last_lng'], 6);
            }

            // Extract formatted location from reverse geo if available
            if (isset($gpsData['reverseGeo']['formattedLocation'])) {
                $trailerInfo['formatted_location'] = $gpsData['reverseGeo']['formattedLocation'];
            }
        }

        // Determine trailer status based on data
        $trailerInfo['status'] = $this->determineTrailerStatus($trailerData);

        // Set default type if not specified
        if (!isset($trailerInfo['type'])) {
            $trailerInfo['type'] = 'trailer'; // Default type
        }

        // Upsert trailer record
        Trailer::updateOrCreate(
            ['external_id' => $externalId],
            $trailerInfo
        );
    }

    /**
     * Extract asset number from trailer name
     */
    protected function extractAssetNumber(string $name): ?string
    {
        // Try to extract number patterns like "Trailer 123", "Platform #456", "789", etc.
        if (preg_match('/(?:trailer|platform|asset|#)?\s*(\d+)/i', $name, $matches)) {
            return $matches[1];
        }

        // If name is just a number
        if (preg_match('/^\d+$/', trim($name))) {
            return trim($name);
        }

        return null;
    }

    /**
     * Determine trailer status based on Samsara data
     */
    protected function determineTrailerStatus(array $trailerData): string
    {
        // Check if trailer is explicitly marked as inactive
        if (isset($trailerData['isActive']) && !$trailerData['isActive']) {
            return 'out_of_service';
        }

        // Check if trailer is moving (has recent GPS data with speed)
        if (isset($trailerData['gps']) && !empty($trailerData['gps'])) {
            $gpsData = end($trailerData['gps']);
            $speed = $gpsData['speedMilesPerHour'] ?? 0;
            
            if ($speed > 5) { // Consider moving if speed > 5 mph
                return 'in_trip';
            }
        }

        // Default to available if no specific indicators
        return 'available';
    }

    /**
     * Mark trailers as inactive if they weren't updated in this sync
     */
    protected function markInactiveTrailers(Carbon $startTime): int
    {
        return DB::table('trailers')
            ->where('synced_at', '<', $startTime)
            ->whereNotNull('external_id') // Only mark Samsara-synced trailers
            ->where('status', '!=', 'out_of_service')
            ->update([
                'status' => 'out_of_service',
                'updated_at' => now(),
            ]);
    }
}