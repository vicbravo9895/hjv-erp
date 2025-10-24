<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnrichVehicleFromSamsara extends Command
{
    protected $signature = 'vehicle:enrich {vehicle_id?} {--samsara-id=} {--all : Enrich all vehicles with external_id}';
    protected $description = 'Enrich vehicle data from Samsara API';

    public function handle()
    {
        $apiToken = config('services.samsara.api_token');

        if (!$apiToken) {
            $this->error("Samsara API token not configured. Set SAMSARA_API_TOKEN in .env");
            return 1;
        }

        // Process all vehicles if --all flag is set
        if ($this->option('all')) {
            return $this->enrichAllVehicles();
        }

        // Process single vehicle
        $vehicleId = $this->argument('vehicle_id');

        if (!$vehicleId) {
            $this->error("Please provide a vehicle_id or use --all flag");
            return 1;
        }

        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            $this->error("Vehicle with ID {$vehicleId} not found");
            return 1;
        }

        $samsaraId = $this->option('samsara-id') ?? $vehicle->external_id;

        if (!$samsaraId) {
            $this->error("No Samsara ID provided. Use --samsara-id option or ensure vehicle has external_id");
            return 1;
        }

        return $this->enrichVehicle($vehicle, $samsaraId);
    }

    protected function enrichAllVehicles(): int
    {
        $vehicles = Vehicle::whereNotNull('external_id')->get();

        if ($vehicles->isEmpty()) {
            $this->warn("No vehicles found with external_id");
            return 0;
        }

        $this->info("Found {$vehicles->count()} vehicles to enrich");
        $this->newLine();

        $bar = $this->output->createProgressBar($vehicles->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($vehicles as $vehicle) {
            try {
                $result = $this->enrichVehicle($vehicle, $vehicle->external_id, false);
                if ($result === 0) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->error("Error enriching vehicle {$vehicle->id}: " . $e->getMessage());
            }

            $bar->advance();

            // Rate limiting: wait 100ms between requests
            usleep(100000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Enrichment completed!");
        $this->info("- Success: {$successCount}");
        $this->info("- Errors: {$errorCount}");

        return $errorCount > 0 ? 1 : 0;
    }

    protected function enrichVehicle(Vehicle $vehicle, string $samsaraId, bool $showDetails = true): int
    {
        if ($showDetails) {
            $this->info("Fetching vehicle data from Samsara for ID: {$samsaraId}");
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer " . config('services.samsara.api_token'),
                'Accept' => 'application/json',
            ])->get("https://api.samsara.com/fleet/vehicles/{$samsaraId}");

            if (!$response->successful()) {
                if ($showDetails) {
                    $this->error("Failed to fetch vehicle data: " . $response->body());
                }
                return 1;
            }

            $data = $response->json('data');

            if (!$data) {
                if ($showDetails) {
                    $this->error("No data returned from Samsara API");
                }
                return 1;
            }

            $vehicle->update([
                'external_id' => $data['id'] ?? $vehicle->external_id,
                'name' => $data['name'] ?? $vehicle->name,
                'vin' => $data['vin'] ?? $vehicle->vin,
                'serial_number' => $data['serial'] ?? $vehicle->serial_number,
                'license_plate' => $data['licensePlate'] ?? null,
                'make' => $data['make'] ?? $vehicle->make,
                'model' => $data['model'] ?? $vehicle->model,
                'year' => $data['year'] ?? $vehicle->year,
                'esn' => $data['esn'] ?? null,
                'camera_serial' => $data['cameraSerial'] ?? null,
                'gateway_model' => $data['gateway']['model'] ?? null,
                'gateway_serial' => $data['gateway']['serial'] ?? null,
                'vehicle_type' => $data['vehicleType'] ?? null,
                'regulation_mode' => $data['vehicleRegulationMode'] ?? null,
                'gross_vehicle_weight' => $data['grossVehicleWeight']['weight'] ?? null,
                'notes' => $data['notes'] ?? null,
                'external_ids' => $data['externalIds'] ?? null,
                'tags' => $data['tags'] ?? null,
                'attributes' => $data['attributes'] ?? null,
                'sensor_configuration' => $data['sensorConfiguration'] ?? null,
                'static_assigned_driver_id' => $data['staticAssignedDriver']['id'] ?? null,
                'static_assigned_driver_name' => $data['staticAssignedDriver']['name'] ?? null,
                'raw_snapshot' => array_merge($vehicle->raw_snapshot ?? [], ['enrichment' => $data]),
                'synced_at' => now(),
            ]);

            if ($showDetails) {
                $this->info("✓ Vehicle enriched successfully!");
                $this->newLine();
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Name', $vehicle->name],
                        ['VIN', $vehicle->vin],
                        ['Make/Model', "{$vehicle->make} {$vehicle->model}"],
                        ['Year', $vehicle->year],
                        ['Type', $vehicle->vehicle_type],
                        ['License Plate', $vehicle->license_plate],
                        ['Tags', count($vehicle->tags ?? [])],
                    ]
                );
            }

            return 0;
        } catch (\Exception $e) {
            if ($showDetails) {
                $this->error("Error: " . $e->getMessage());
            }
            return 1;
        }
    }
}
