<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SamsaraClient;
use App\Services\SamsaraSyncLogService;
use App\Console\Commands\SyncVehicles;
use App\Console\Commands\SyncTrailers;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\SamsaraSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class SamsaraIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('samsara.api_token', 'test-token');
        Config::set('samsara.api_url', 'https://api.samsara.com');
        Config::set('samsara.endpoints.vehicles', '/fleet/vehicles/stats');
        Config::set('samsara.endpoints.trailers', '/fleet/trailers/stats');
        Config::set('samsara.sync.page_limit', 10);
    }

    public function test_samsara_client_can_connect_to_api()
    {
        // Arrange
        Http::fake([
            'api.samsara.com/fleet/vehicles*' => Http::response([
                'data' => [],
                'pagination' => ['hasNextPage' => false]
            ], 200)
        ]);

        $client = new SamsaraClient();

        // Act
        $result = $client->testConnection();

        // Assert
        $this->assertTrue($result);
    }

    public function test_samsara_client_handles_api_errors_gracefully()
    {
        // Arrange
        Http::fake([
            'api.samsara.com/fleet/vehicles*' => Http::response([], 401)
        ]);

        $client = new SamsaraClient();

        // Act
        $result = $client->testConnection();

        // Assert
        $this->assertFalse($result);
    }

    public function test_sync_vehicles_command_processes_vehicle_data()
    {
        // Arrange
        $vehicleData = [
            'data' => [
                [
                    'id' => 'samsara-123',
                    'name' => 'Truck 001',
                    'vin' => 'TEST123456789',
                    'gps' => [
                        [
                            'latitude' => 37.7749,
                            'longitude' => -122.4194,
                            'speedMilesPerHour' => 0,
                            'time' => '2024-01-15T10:00:00Z',
                            'reverseGeo' => [
                                'formattedLocation' => 'San Francisco, CA'
                            ]
                        ]
                    ],
                    'obdOdometerMeters' => [
                        [
                            'value' => 150000,
                            'time' => '2024-01-15T10:00:00Z'
                        ]
                    ],
                    'engineStates' => [
                        [
                            'value' => 'Off',
                            'time' => '2024-01-15T10:00:00Z'
                        ]
                    ],
                    'fuelPercents' => [
                        [
                            'value' => 75.5,
                            'time' => '2024-01-15T10:00:00Z'
                        ]
                    ]
                ]
            ],
            'pagination' => ['hasNextPage' => false]
        ];

        Http::fake([
            'api.samsara.com/fleet/vehicles/stats*' => Http::response($vehicleData, 200)
        ]);

        // Act
        Artisan::call('samsara:sync-vehicles', ['--force' => true]);

        // Assert
        $this->assertDatabaseHas('vehicles', [
            'external_id' => 'samsara-123',
            'name' => 'Truck 001',
            'vin' => 'TEST123456789',
            'last_lat' => 37.7749,
            'last_lng' => -122.4194,
            'last_odometer_km' => 150.0, // 150000 meters = 150 km
            'last_fuel_percent' => 75.5,
            'formatted_location' => 'San Francisco, CA'
        ]);

        $this->assertDatabaseHas('samsara_sync_logs', [
            'sync_type' => 'vehicles',
            'status' => 'completed'
        ]);
    }

    public function test_sync_trailers_command_processes_trailer_data()
    {
        // Arrange
        $trailerData = [
            'data' => [
                [
                    'id' => 'samsara-trailer-456',
                    'name' => 'Trailer T001',
                    'gps' => [
                        [
                            'latitude' => 34.0522,
                            'longitude' => -118.2437,
                            'speedMilesPerHour' => 65,
                            'time' => '2024-01-15T14:00:00Z',
                            'reverseGeo' => [
                                'formattedLocation' => 'Los Angeles, CA'
                            ]
                        ]
                    ]
                ]
            ],
            'pagination' => ['hasNextPage' => false]
        ];

        Http::fake([
            'api.samsara.com/fleet/trailers/stats*' => Http::response($trailerData, 200)
        ]);

        // Act
        Artisan::call('samsara:sync-trailers', ['--force' => true]);

        // Assert
        $this->assertDatabaseHas('trailers', [
            'external_id' => 'samsara-trailer-456',
            'name' => 'Trailer T001',
            'last_lat' => 34.0522,
            'last_lng' => -118.2437,
            'last_speed_mph' => 65,
            'formatted_location' => 'Los Angeles, CA'
        ]);

        $this->assertDatabaseHas('samsara_sync_logs', [
            'sync_type' => 'trailers',
            'status' => 'completed'
        ]);
    }

    public function test_sync_vehicles_handles_pagination()
    {
        // Arrange - First page
        $firstPageData = [
            'data' => [
                [
                    'id' => 'samsara-001',
                    'name' => 'Truck 001',
                    'vin' => 'VIN001'
                ]
            ],
            'pagination' => [
                'hasNextPage' => true,
                'endCursor' => 'cursor-123'
            ]
        ];

        // Second page
        $secondPageData = [
            'data' => [
                [
                    'id' => 'samsara-002',
                    'name' => 'Truck 002',
                    'vin' => 'VIN002'
                ]
            ],
            'pagination' => [
                'hasNextPage' => false
            ]
        ];

        Http::fake([
            'api.samsara.com/fleet/vehicles/stats*' => function ($request) use ($firstPageData, $secondPageData) {
                $url = $request->url();
                if (str_contains($url, 'after=cursor-123')) {
                    return Http::response($secondPageData, 200);
                }
                return Http::response($firstPageData, 200);
            }
        ]);

        // Act
        Artisan::call('samsara:sync-vehicles', ['--force' => true, '--limit' => 10]);

        // Assert
        $this->assertDatabaseHas('vehicles', [
            'external_id' => 'samsara-001',
            'name' => 'Truck 001'
        ]);

        $this->assertDatabaseHas('vehicles', [
            'external_id' => 'samsara-002',
            'name' => 'Truck 002'
        ]);
    }

    public function test_sync_vehicles_marks_inactive_vehicles()
    {
        // Arrange - Create existing vehicle that won't be in sync
        $existingVehicle = Vehicle::create([
            'external_id' => 'old-vehicle',
            'name' => 'Old Vehicle',
            'status' => 'available',
            'synced_at' => now()->subHours(2)
        ]);

        Http::fake([
            'api.samsara.com/fleet/vehicles/stats*' => Http::response([
                'data' => [
                    [
                        'id' => 'samsara-new',
                        'name' => 'New Vehicle'
                    ]
                ],
                'pagination' => ['hasNextPage' => false]
            ], 200)
        ]);

        // Act
        Artisan::call('samsara:sync-vehicles', ['--force' => true]);

        // Assert - Old vehicle should be marked as out of service
        $this->assertDatabaseHas('vehicles', [
            'external_id' => 'old-vehicle',
            'status' => 'out_of_service'
        ]);

        // New vehicle should be created
        $this->assertDatabaseHas('vehicles', [
            'external_id' => 'samsara-new',
            'name' => 'New Vehicle'
        ]);
    }

    public function test_sync_logs_error_handling()
    {
        // Arrange - Simulate API returning 500 error consistently
        Http::fake([
            'api.samsara.com/fleet/vehicles/stats*' => Http::response([
                'error' => 'Internal Server Error'
            ], 500)
        ]);

        // Act - This should complete but with errors logged
        Artisan::call('samsara:sync-vehicles', ['--force' => true]);

        // Assert - Should complete successfully even with API errors
        // The sync command handles API errors gracefully and logs them
        $this->assertDatabaseHas('samsara_sync_logs', [
            'sync_type' => 'vehicles',
            'status' => 'completed'
        ]);
        
        // Verify that no vehicles were synced due to API errors
        $syncLog = \App\Models\SamsaraSyncLog::where('sync_type', 'vehicles')->latest()->first();
        $this->assertEquals(0, $syncLog->synced_records);
    }

    public function test_samsara_client_iterates_vehicles_with_callback()
    {
        // Arrange
        $vehicleData = [
            'data' => [
                ['id' => 'vehicle-1', 'name' => 'Truck 1'],
                ['id' => 'vehicle-2', 'name' => 'Truck 2']
            ],
            'pagination' => ['hasNextPage' => false]
        ];

        Http::fake([
            'api.samsara.com/fleet/vehicles/stats*' => Http::response($vehicleData, 200)
        ]);

        $client = new SamsaraClient();
        $processedVehicles = [];

        // Act
        $stats = $client->iterateVehicles(function ($vehicle) use (&$processedVehicles) {
            $processedVehicles[] = $vehicle['id'];
        });

        // Assert
        $this->assertEquals(2, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
        $this->assertContains('vehicle-1', $processedVehicles);
        $this->assertContains('vehicle-2', $processedVehicles);
    }

    public function test_samsara_client_handles_callback_errors()
    {
        // Arrange
        $vehicleData = [
            'data' => [
                ['id' => 'vehicle-1', 'name' => 'Truck 1'],
                ['id' => 'vehicle-2', 'name' => 'Truck 2']
            ],
            'pagination' => ['hasNextPage' => false]
        ];

        Http::fake([
            'api.samsara.com/fleet/vehicles/stats*' => Http::response($vehicleData, 200)
        ]);

        $client = new SamsaraClient();

        // Act
        $stats = $client->iterateVehicles(function ($vehicle) {
            if ($vehicle['id'] === 'vehicle-2') {
                throw new \Exception('Processing error');
            }
        });

        // Assert
        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
    }

    public function test_sync_respects_operating_hours_configuration()
    {
        // Arrange
        Config::set('samsara.sync.enabled', false);

        Http::fake();

        // Act
        $exitCode = Artisan::call('samsara:sync-vehicles');

        // Assert
        $this->assertEquals(0, $exitCode);
        Http::assertNothingSent();
    }
}