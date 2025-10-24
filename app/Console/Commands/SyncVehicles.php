<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\SamsaraClient;
use App\Services\SamsaraSyncLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'samsara:sync-vehicles 
                            {--limit=100 : Number of records per page}
                            {--tag-ids= : Comma-separated tag IDs to filter vehicles}
                            {--force : Force sync even outside operating hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize vehicles data from Samsara API';

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
        if (!$this->option('force') && !$this->syncLogService->shouldRunSync('vehicles')) {
            $this->info('Vehicle sync is disabled or outside operating hours');
            return self::SUCCESS;
        }

        $startTime = now();
        $limit = (int) $this->option('limit');
        
        // Get tag IDs from command option or environment variable
        $tagIds = $this->option('tag-ids') 
            ? explode(',', $this->option('tag-ids'))
            : (config('services.samsara.default_tag_ids') 
                ? explode(',', config('services.samsara.default_tag_ids'))
                : null);

        // Start sync logging
        $syncLog = $this->syncLogService->startSync('vehicles', [
            'limit' => $limit,
            'tag_ids' => $tagIds,
            'forced' => $this->option('force'),
        ]);

        if ($tagIds) {
            $this->info("Starting vehicle synchronization with tag filter: " . implode(', ', $tagIds));
        } else {
            $this->info("Starting vehicle synchronization (no tag filter)...");
        }

        try {
            $syncedCount = 0;
            $errorCount = 0;

            // Iterate through all vehicles from Samsara
            $stats = $this->samsaraClient->iterateVehicles(
                function ($vehicleData) use (&$syncedCount, &$errorCount) {
                    try {
                        $this->processVehicle($vehicleData);
                        $syncedCount++;
                        
                        if ($syncedCount % 50 === 0) {
                            $this->info("Processed {$syncedCount} vehicles...");
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('Error processing vehicle', [
                            'vehicle_id' => $vehicleData['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                },
                $limit,
                $tagIds
            );

            // Mark vehicles as inactive if they weren't updated in this sync
            $inactiveCount = $this->markInactiveVehicles($startTime);

            // Complete sync logging
            $this->syncLogService->completeSync($syncLog, $syncedCount, [
                'errors' => $errorCount,
                'inactive_marked' => $inactiveCount,
                'api_stats' => $stats,
            ]);

            $this->info("Vehicle synchronization completed!");
            $this->info("- Synced: {$syncedCount} vehicles");
            $this->info("- Errors: {$errorCount}");
            $this->info("- Marked inactive: {$inactiveCount}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->syncLogService->failSync($syncLog, $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->error("Vehicle synchronization failed: " . $e->getMessage());
            Log::error('Vehicle sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Process individual vehicle data from Samsara
     */
    protected function processVehicle(array $vehicleData): void
    {
        $externalId = $vehicleData['id'];
        
        // Extract basic vehicle information
        $vehicleInfo = [
            'external_id' => $externalId,
            'name' => $vehicleData['name'] ?? null,
            'vin' => $vehicleData['vin'] ?? null,
            'serial_number' => $vehicleData['serialNumber'] ?? null,
            'synced_at' => now(),
            'raw_snapshot' => $vehicleData,
        ];

        // Extract VIN from externalIds if not directly available
        if (empty($vehicleInfo['vin']) && isset($vehicleData['externalIds']['samsara.vin'])) {
            $vehicleInfo['vin'] = $vehicleData['externalIds']['samsara.vin'];
        }

        // Extract serial number from externalIds if not directly available
        if (empty($vehicleInfo['serial_number']) && isset($vehicleData['externalIds']['samsara.serial'])) {
            $vehicleInfo['serial_number'] = $vehicleData['externalIds']['samsara.serial'];
        }

        // Set default make/model if not already set in database
        // We'll only update these if they're currently null to preserve manual entries
        $existingVehicle = Vehicle::where('external_id', $externalId)->first();
        if (!$existingVehicle || empty($existingVehicle->make)) {
            $vehicleInfo['make'] = 'Samsara Vehicle'; // Default make for Samsara vehicles
        }
        if (!$existingVehicle || empty($existingVehicle->model)) {
            $vehicleInfo['model'] = 'Unknown Model'; // Default model
        }

        // Extract unit number from name if available
        if (!empty($vehicleData['name'])) {
            $vehicleInfo['unit_number'] = $this->extractUnitNumber($vehicleData['name']);
        }

        // Process GPS data directly from vehicle data (not stats)
        if (isset($vehicleData['gps']) && !empty($vehicleData['gps'])) {
            $gpsData = end($vehicleData['gps']); // Get latest GPS data
            $vehicleInfo['last_lat'] = $gpsData['latitude'] ?? null;
            $vehicleInfo['last_lng'] = $gpsData['longitude'] ?? null;
            $vehicleInfo['last_speed_mph'] = $gpsData['speedMilesPerHour'] ?? null;
            $vehicleInfo['last_location_at'] = isset($gpsData['time']) ? 
                Carbon::parse($gpsData['time']) : null;
            
            // Format location if coordinates are available
            if ($vehicleInfo['last_lat'] && $vehicleInfo['last_lng']) {
                $vehicleInfo['formatted_location'] = 
                    number_format($vehicleInfo['last_lat'], 6) . ', ' . 
                    number_format($vehicleInfo['last_lng'], 6);
            }

            // Extract formatted location from reverse geo if available
            if (isset($gpsData['reverseGeo']['formattedLocation'])) {
                $vehicleInfo['formatted_location'] = $gpsData['reverseGeo']['formattedLocation'];
            }
        }

        // Process odometer data
        if (isset($vehicleData['obdOdometerMeters']) && !empty($vehicleData['obdOdometerMeters'])) {
            $odometerData = end($vehicleData['obdOdometerMeters']);
            $vehicleInfo['last_odometer_km'] = isset($odometerData['value']) ? 
                round($odometerData['value'] / 1000, 2) : null; // Convert meters to km
        }

        // Process engine state data
        if (isset($vehicleData['engineStates']) && !empty($vehicleData['engineStates'])) {
            $engineData = end($vehicleData['engineStates']);
            $vehicleInfo['last_engine_state'] = $this->mapEngineState($engineData['value'] ?? null);
        }

        // Process fuel percentage data
        if (isset($vehicleData['fuelPercents']) && !empty($vehicleData['fuelPercents'])) {
            $fuelData = end($vehicleData['fuelPercents']);
            $vehicleInfo['last_fuel_percent'] = $fuelData['value'] ?? null;
        }

        // Process driver assignment
        if (isset($vehicleData['driverAssignments']) && !empty($vehicleData['driverAssignments'])) {
            $driverAssignment = end($vehicleData['driverAssignments']);
            $vehicleInfo['current_driver_external_id'] = $driverAssignment['driverId'] ?? null;
            $vehicleInfo['current_driver_name'] = $driverAssignment['driverName'] ?? null;
        }

        // Determine vehicle status based on data
        $vehicleInfo['status'] = $this->determineVehicleStatus($vehicleData);

        // Upsert vehicle record
        Vehicle::updateOrCreate(
            ['external_id' => $externalId],
            $vehicleInfo
        );
    }

    /**
     * Extract unit number from vehicle name
     */
    protected function extractUnitNumber(string $name): ?string
    {
        // Try to extract number patterns like "Unit 123", "Truck #456", "789", etc.
        if (preg_match('/(?:unit|truck|#)?\s*(\d+)/i', $name, $matches)) {
            return $matches[1];
        }

        // If name is just a number
        if (preg_match('/^\d+$/', trim($name))) {
            return trim($name);
        }

        return null;
    }

    /**
     * Map Samsara engine state to local engine state
     */
    protected function mapEngineState(?string $samsaraState): ?string
    {
        if (!$samsaraState) {
            return null;
        }

        $mapping = config('samsara.mapping.engine_states', []);
        return $mapping[$samsaraState] ?? strtolower($samsaraState);
    }

    /**
     * Determine vehicle status based on Samsara data
     */
    protected function determineVehicleStatus(array $vehicleData): string
    {
        // Check if vehicle is explicitly marked as inactive
        if (isset($vehicleData['isActive']) && !$vehicleData['isActive']) {
            return 'out_of_service';
        }

        // Check engine state for availability
        if (isset($vehicleData['engineStates']) && !empty($vehicleData['engineStates'])) {
            $engineData = end($vehicleData['engineStates']);
            $engineState = $engineData['value'] ?? null;
            
            if ($engineState === 'Running') {
                return 'in_trip'; // Assume running engine means in trip
            }
        }

        // Check if vehicle is moving based on GPS speed
        if (isset($vehicleData['gps']) && !empty($vehicleData['gps'])) {
            $gpsData = end($vehicleData['gps']);
            $speed = $gpsData['speedMilesPerHour'] ?? 0;
            
            if ($speed > 5) { // Consider moving if speed > 5 mph
                return 'in_trip';
            }
        }

        // Default to available if no specific indicators
        return 'available';
    }

    /**
     * Mark vehicles as inactive if they weren't updated in this sync
     */
    protected function markInactiveVehicles(Carbon $startTime): int
    {
        return DB::table('vehicles')
            ->where('synced_at', '<', $startTime)
            ->whereNotNull('external_id') // Only mark Samsara-synced vehicles
            ->where('status', '!=', 'out_of_service')
            ->update([
                'status' => 'out_of_service',
                'updated_at' => now(),
            ]);
    }
}