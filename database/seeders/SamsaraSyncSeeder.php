<?php

namespace Database\Seeders;

use App\Models\SamsaraSyncLog;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class SamsaraSyncSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        $syncTypes = ['vehicles', 'trailers', 'drivers'];
        $statuses = ['completed', 'failed', 'running'];
        
        // Crear logs de sincronización de los últimos 30 días
        for ($i = 0; $i < 200; $i++) {
            $syncType = $faker->randomElement($syncTypes);
            $status = $faker->randomElement($statuses);
            $startedAt = $faker->dateTimeBetween('-30 days', 'now');
            
            $completedAt = null;
            $syncedRecords = null;
            $durationSeconds = null;
            $errorMessage = null;
            $errorDetails = null;
            
            if ($status === 'completed') {
                $completedAt = $faker->dateTimeBetween($startedAt, $startedAt->format('Y-m-d H:i:s') . ' +2 hours');
                $syncedRecords = $faker->numberBetween(10, 100);
                $durationSeconds = $faker->numberBetween(30, 1800); // 30 segundos a 30 minutos
            } elseif ($status === 'failed') {
                $completedAt = $faker->dateTimeBetween($startedAt, $startedAt->format('Y-m-d H:i:s') . ' +1 hour');
                $syncedRecords = $faker->numberBetween(0, 50);
                $durationSeconds = $faker->numberBetween(10, 900);
                
                $errorMessages = [
                    'API rate limit exceeded',
                    'Authentication failed',
                    'Network timeout',
                    'Invalid response format',
                    'Missing required fields',
                    'Database connection error',
                ];
                $errorMessage = $faker->randomElement($errorMessages);
                $errorDetails = [
                    'error_code' => $faker->randomElement(['AUTH_ERROR', 'RATE_LIMIT', 'NETWORK_ERROR', 'VALIDATION_ERROR']),
                    'http_status' => $faker->randomElement([401, 429, 500, 503]),
                    'details' => $faker->sentence(),
                ];
            } else { // running
                $syncedRecords = $faker->numberBetween(0, 20);
                $durationSeconds = $faker->numberBetween(10, 300);
            }
            
            SamsaraSyncLog::create([
                'sync_type' => $syncType,
                'status' => $status,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'synced_records' => $syncedRecords,
                'duration_seconds' => $durationSeconds,
                'error_message' => $errorMessage,
                'params' => $this->generateParams($faker, $syncType),
                'additional_data' => $this->generateAdditionalData($faker, $syncType, $status),
                'error_details' => $errorDetails,
            ]);
        }
    }
    
    private function generateParams($faker, $syncType): array
    {
        $baseParams = [
            'api_version' => 'v1',
            'limit' => $faker->numberBetween(50, 200),
            'offset' => $faker->numberBetween(0, 500),
        ];
        
        switch ($syncType) {
            case 'vehicles':
                $baseParams['include_inactive'] = $faker->boolean();
                $baseParams['vehicle_types'] = $faker->randomElements(['truck', 'trailer'], $faker->numberBetween(1, 2));
                break;
            case 'trailers':
                $baseParams['include_maintenance'] = $faker->boolean();
                break;
            case 'drivers':
                $baseParams['include_inactive_drivers'] = $faker->boolean();
                $baseParams['license_check'] = $faker->boolean();
                break;
        }
        
        return $baseParams;
    }
    
    private function generateAdditionalData($faker, $syncType, $status): ?array
    {
        if ($status === 'running') {
            return null;
        }
        
        return [
            'total_api_calls' => $faker->numberBetween(1, 10),
            'data_size_mb' => $faker->randomFloat(2, 0.1, 50),
            'cache_hits' => $faker->numberBetween(0, 100),
            'new_records' => $faker->numberBetween(0, 50),
            'updated_records' => $faker->numberBetween(0, 30),
            'skipped_records' => $faker->numberBetween(0, 10),
        ];
    }
}