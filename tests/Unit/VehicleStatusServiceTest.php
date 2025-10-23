<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VehicleStatusService;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VehicleStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VehicleStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VehicleStatusService();
    }

    public function test_mark_vehicle_in_trip_when_available()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'available']);

        // Act
        $result = $this->service->markVehicleInTrip($vehicle);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('in_trip', $vehicle->fresh()->status);
    }

    public function test_mark_vehicle_in_trip_when_not_available()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'maintenance']);

        // Act
        $result = $this->service->markVehicleInTrip($vehicle);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('maintenance', $vehicle->fresh()->status);
    }

    public function test_mark_trailer_in_trip_when_available()
    {
        // Arrange
        $trailer = Trailer::factory()->create(['status' => 'available']);

        // Act
        $result = $this->service->markTrailerInTrip($trailer);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('in_trip', $trailer->fresh()->status);
    }

    public function test_mark_trailer_in_trip_when_not_available()
    {
        // Arrange
        $trailer = Trailer::factory()->create(['status' => 'maintenance']);

        // Act
        $result = $this->service->markTrailerInTrip($trailer);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('maintenance', $trailer->fresh()->status);
    }

    public function test_mark_vehicle_available()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'in_trip']);

        // Act
        $result = $this->service->markVehicleAvailable($vehicle);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('available', $vehicle->fresh()->status);
    }

    public function test_mark_trailer_available()
    {
        // Arrange
        $trailer = Trailer::factory()->create(['status' => 'in_trip']);

        // Act
        $result = $this->service->markTrailerAvailable($trailer);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('available', $trailer->fresh()->status);
    }

    public function test_mark_vehicle_in_maintenance_when_available()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'available']);

        // Act
        $result = $this->service->markVehicleInMaintenance($vehicle);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('maintenance', $vehicle->fresh()->status);
    }

    public function test_mark_vehicle_in_maintenance_when_already_in_maintenance()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'maintenance']);

        // Act
        $result = $this->service->markVehicleInMaintenance($vehicle);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('maintenance', $vehicle->fresh()->status);
    }

    public function test_mark_vehicle_in_maintenance_when_in_trip_fails()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'in_trip']);

        // Act
        $result = $this->service->markVehicleInMaintenance($vehicle);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('in_trip', $vehicle->fresh()->status);
    }

    public function test_mark_vehicle_out_of_service_when_available()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'available']);

        // Act
        $result = $this->service->markVehicleOutOfService($vehicle);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('out_of_service', $vehicle->fresh()->status);
    }

    public function test_mark_vehicle_out_of_service_when_in_trip_fails()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create(['status' => 'in_trip']);

        // Act
        $result = $this->service->markVehicleOutOfService($vehicle);

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('in_trip', $vehicle->fresh()->status);
    }

    public function test_get_available_vehicles()
    {
        // Arrange
        Vehicle::factory()->create(['status' => 'available']);
        Vehicle::factory()->create(['status' => 'available']);
        Vehicle::factory()->create(['status' => 'in_trip']);
        Vehicle::factory()->create(['status' => 'maintenance']);

        // Act
        $availableVehicles = $this->service->getAvailableVehicles();

        // Assert
        $this->assertCount(2, $availableVehicles);
        $this->assertTrue($availableVehicles->every(fn($v) => $v->status === 'available'));
    }

    public function test_get_available_trailers()
    {
        // Arrange
        Trailer::factory()->create(['status' => 'available']);
        Trailer::factory()->create(['status' => 'available']);
        Trailer::factory()->create(['status' => 'in_trip']);
        Trailer::factory()->create(['status' => 'maintenance']);

        // Act
        $availableTrailers = $this->service->getAvailableTrailers();

        // Assert
        $this->assertCount(2, $availableTrailers);
        $this->assertTrue($availableTrailers->every(fn($t) => $t->status === 'available'));
    }

    public function test_has_active_trips_returns_true_when_active_trips_exist()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create();
        $operator = User::factory()->state(['role' => 'operador'])->create();
        Trip::factory()->create([
            'truck_id' => $vehicle->id,
            'operator_id' => $operator->id,
            'status' => 'in_progress'
        ]);

        // Act
        $hasActiveTrips = $this->service->hasActiveTrips($vehicle);

        // Assert
        $this->assertTrue($hasActiveTrips);
    }

    public function test_has_active_trips_returns_false_when_no_active_trips()
    {
        // Arrange
        $vehicle = Vehicle::factory()->create();
        $operator = User::factory()->state(['role' => 'operador'])->create();
        Trip::factory()->create([
            'truck_id' => $vehicle->id,
            'operator_id' => $operator->id,
            'status' => 'completed'
        ]);

        // Act
        $hasActiveTrips = $this->service->hasActiveTrips($vehicle);

        // Assert
        $this->assertFalse($hasActiveTrips);
    }

    public function test_sync_vehicle_statuses_from_trips()
    {
        // Arrange
        $vehicleWithActiveTrip = Vehicle::factory()->create(['status' => 'in_trip']);
        $vehicleWithoutActiveTrip = Vehicle::factory()->create(['status' => 'in_trip']);
        $trailerWithActiveTrip = Trailer::factory()->create(['status' => 'in_trip']);
        $trailerWithoutActiveTrip = Trailer::factory()->create(['status' => 'in_trip']);
        
        $operator = User::factory()->state(['role' => 'operador'])->create();

        // Create active trip for one vehicle and trailer
        Trip::factory()->create([
            'truck_id' => $vehicleWithActiveTrip->id,
            'trailer_id' => $trailerWithActiveTrip->id,
            'operator_id' => $operator->id,
            'status' => 'in_progress'
        ]);

        // Act
        $result = $this->service->syncVehicleStatusesFromTrips();

        // Assert
        $this->assertEquals(1, $result['vehicles']);
        $this->assertEquals(1, $result['trailers']);
        
        $this->assertEquals('in_trip', $vehicleWithActiveTrip->fresh()->status);
        $this->assertEquals('available', $vehicleWithoutActiveTrip->fresh()->status);
        $this->assertEquals('in_trip', $trailerWithActiveTrip->fresh()->status);
        $this->assertEquals('available', $trailerWithoutActiveTrip->fresh()->status);
    }

    public function test_get_vehicle_status_stats()
    {
        // Arrange
        Vehicle::factory()->create(['status' => 'available']);
        Vehicle::factory()->create(['status' => 'available']);
        Vehicle::factory()->create(['status' => 'in_trip']);
        Vehicle::factory()->create(['status' => 'maintenance']);
        Vehicle::factory()->create(['status' => 'out_of_service']);

        // Act
        $stats = $this->service->getVehicleStatusStats();

        // Assert
        $this->assertEquals(2, $stats['available']);
        $this->assertEquals(1, $stats['in_trip']);
        $this->assertEquals(1, $stats['maintenance']);
        $this->assertEquals(1, $stats['out_of_service']);
        $this->assertEquals(5, $stats['total']);
    }

    public function test_get_trailer_status_stats()
    {
        // Arrange
        Trailer::factory()->create(['status' => 'available']);
        Trailer::factory()->create(['status' => 'available']);
        Trailer::factory()->create(['status' => 'in_trip']);
        Trailer::factory()->create(['status' => 'maintenance']);

        // Act
        $stats = $this->service->getTrailerStatusStats();

        // Assert
        $this->assertEquals(2, $stats['available']);
        $this->assertEquals(1, $stats['in_trip']);
        $this->assertEquals(1, $stats['maintenance']);
        $this->assertEquals(0, $stats['out_of_service']);
        $this->assertEquals(4, $stats['total']);
    }
}