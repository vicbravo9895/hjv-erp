<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnrichVehicleFromSamsara extends Command
{
    protected $signature = 'vehicle:enrich {vehicle_id} {--samsara-id=}';
    protected $description = 'Enrich vehicle data from Samsara API';

    public function handle()
    {
        $vehicleId = $this->argument('vehicle_id');
        $samsaraId = $this->option('samsara-id');
        
        $vehicle = Vehicle::find($vehicleId);
        
        if (!$vehicle) {
            $this->error("Vehicle with ID {$vehicleId} not found");
            return 1;
        }

        $samsaraVehicleId = $samsaraId ?? $vehicle->external_id;
        
        if (!$samsaraVehicleId) {
            $this->error("No Samsara ID provided. Use --samsara-id option or ensure vehicle has external_id");
            return 1;
        }

        $apiToken = config('services.samsara.api_token');
        
        if (!$apiToken) {
            $this->error("Samsara API token not configured. Set SAMSARA_API_TOKEN in .env");
            return 1;
        }

        $this->info("Fetching vehicle data from Samsara for ID: {$samsaraVehicleId}");

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Accept' => 'application/json',
            ])->get("https://api.samsara.com/fleet/vehicles/{$samsaraVehicleId}");

            if (!$response->successful()) {
                $this->error("Failed to fetch vehicle data: " . $response->body());
                return 1;
            }

            $data = $response->json('data');
            
            if (!$data) {
                $this->error("No data returned from Samsara API");
                return 1;
            }

            $this->info("Enriching vehicle data...");

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
                'raw_snapshot' => $data,
                'synced_at' => now(),
            ]);

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

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
