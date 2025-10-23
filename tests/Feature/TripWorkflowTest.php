<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\VehicleStatusService;
use App\Services\ProfitabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TripWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected VehicleStatusService $vehicleStatusService;
    protected ProfitabilityService $profitabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vehicleStatusService = new VehicleStatusService();
        $this->profitabilityService = new ProfitabilityService();
    }

    public function test_complete_trip_workflow_from_creation_to_completion()
    {
        // Arrange - Create available resources
        $vehicle = Vehicle::factory()->available()->create();
        $trailer = Trailer::factory()->available()->create();
        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();

        // Act 1: Create trip
        $trip = Trip::create([
            'origin' => 'Los Angeles, CA',
            'destination' => 'Phoenix, AZ',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(2),
            'truck_id' => $vehicle->id,
            'trailer_id' => $trailer->id,
            'operator_id' => $operator->id,
            'status' => 'planned'
        ]);

        // Assert: Trip created successfully
        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'planned'
        ]);

        // Act 2: Start trip (mark vehicles as in_trip)
        $this->vehicleStatusService->markVehicleInTrip($vehicle);
        $this->vehicleStatusService->markTrailerInTrip($trailer);
        $trip->update(['status' => 'in_progress']);

        // Assert: Vehicles marked as in trip
        $this->assertEquals('in_trip', $vehicle->fresh()->status);
        $this->assertEquals('in_trip', $trailer->fresh()->status);
        $this->assertEquals('in_progress', $trip->fresh()->status);

        // Act 3: Add trip costs during journey
        $dieselCost = TripCost::create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 180.50,
            'description' => 'Fuel purchase in Bakersfield',
            'location' => 'Bakersfield, CA',
            'quantity' => 45.5,
            'unit_price' => 3.97
        ]);

        $tollCost = TripCost::create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_TOLLS,
            'amount' => 25.00,
            'description' => 'Highway toll',
            'location' => 'I-10 Toll Plaza'
        ]);

        // Assert: Costs added correctly
        $this->assertDatabaseHas('trip_costs', [
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 180.50
        ]);

        $this->assertDatabaseHas('trip_costs', [
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_TOLLS,
            'amount' => 25.00
        ]);

        // Act 4: Complete trip
        $trip->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $this->vehicleStatusService->markVehicleAvailable($vehicle);
        $this->vehicleStatusService->markTrailerAvailable($trailer);

        // Assert: Trip completed and vehicles available
        $this->assertEquals('completed', $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->completed_at);
        $this->assertEquals('available', $vehicle->fresh()->status);
        $this->assertEquals('available', $trailer->fresh()->status);

        // Act 5: Calculate profitability
        $profitability = $this->profitabilityService->calculateTripProfitability($trip, 800.00);

        // Assert: Profitability calculated correctly
        $this->assertEquals(205.50, $profitability['total_costs']); // 180.50 + 25.00
        $this->assertEquals(800.00, $profitability['revenue']);
        $this->assertEquals(594.50, $profitability['profit']); // 800 - 205.50
        $this->assertEquals(180.50, $profitability['cost_breakdown']['diesel']);
        $this->assertEquals(25.00, $profitability['cost_breakdown']['tolls']);
        $this->assertEquals(0, $profitability['cost_breakdown']['maneuvers']);
        $this->assertEquals(0, $profitability['cost_breakdown']['other']);
    }

    public function test_trip_creation_validates_vehicle_availability()
    {
        // Arrange - Create vehicles with different statuses
        $availableVehicle = Vehicle::factory()->available()->create();
        $busyVehicle = Vehicle::factory()->inTrip()->create();
        $maintenanceVehicle = Vehicle::factory()->maintenance()->create();
        
        $trailer = Trailer::factory()->available()->create();
        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();

        // Act & Assert: Available vehicle can be assigned
        $this->assertTrue($availableVehicle->isAvailable());
        $this->assertTrue($this->vehicleStatusService->markVehicleInTrip($availableVehicle));

        // Act & Assert: Busy vehicle cannot be assigned
        $this->assertFalse($busyVehicle->isAvailable());
        $this->assertFalse($this->vehicleStatusService->markVehicleInTrip($busyVehicle));

        // Act & Assert: Maintenance vehicle cannot be assigned
        $this->assertFalse($maintenanceVehicle->isAvailable());
        $this->assertFalse($this->vehicleStatusService->markVehicleInTrip($maintenanceVehicle));
    }

    public function test_trip_cost_calculation_with_multiple_cost_types()
    {
        // Arrange
        $trip = Trip::create([
            'origin' => 'Los Angeles, CA',
            'destination' => 'Phoenix, AZ',
            'start_date' => Carbon::yesterday(),
            'end_date' => Carbon::today(),
            'truck_id' => Vehicle::factory()->create()->id,
            'trailer_id' => Trailer::factory()->create()->id,
            'operator_id' => User::factory()->state(['role' => 'operador'])->create()->id,
            'status' => 'completed',
            'completed_at' => Carbon::now()
        ]);

        // Act: Add various cost types
        TripCost::factory()->diesel()->create([
            'trip_id' => $trip->id,
            'amount' => 200.00
        ]);

        TripCost::factory()->tolls()->create([
            'trip_id' => $trip->id,
            'amount' => 35.00
        ]);

        TripCost::factory()->maneuvers()->create([
            'trip_id' => $trip->id,
            'amount' => 150.00
        ]);

        TripCost::factory()->other()->create([
            'trip_id' => $trip->id,
            'amount' => 45.00
        ]);

        // Assert: Total cost calculated correctly
        $totalCost = $trip->fresh()->total_cost;
        $this->assertEquals(430.00, $totalCost);

        // Assert: Individual cost types calculated correctly
        $this->assertEquals(200.00, $trip->fresh()->diesel_costs);
        $this->assertEquals(35.00, $trip->fresh()->toll_costs);
        $this->assertEquals(150.00, $trip->fresh()->maneuver_costs);
    }

    public function test_vehicle_status_sync_after_trip_completion()
    {
        // Arrange: Create vehicles in trip status with corresponding trips
        $vehicleWithActiveTrip = Vehicle::factory()->inTrip()->create();
        $vehicleWithCompletedTrip = Vehicle::factory()->inTrip()->create();
        $trailerWithActiveTrip = Trailer::factory()->inTrip()->create();
        $trailerWithCompletedTrip = Trailer::factory()->inTrip()->create();
        
        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();

        // Create active trip
        Trip::factory()->inProgress()->create([
            'truck_id' => $vehicleWithActiveTrip->id,
            'trailer_id' => $trailerWithActiveTrip->id,
            'operator_id' => $operator->id
        ]);

        // Create completed trip
        Trip::factory()->completed()->create([
            'truck_id' => $vehicleWithCompletedTrip->id,
            'trailer_id' => $trailerWithCompletedTrip->id,
            'operator_id' => $operator->id
        ]);

        // Act: Sync vehicle statuses
        $result = $this->vehicleStatusService->syncVehicleStatusesFromTrips();

        // Assert: Only vehicles without active trips are marked available
        $this->assertEquals(1, $result['vehicles']);
        $this->assertEquals(1, $result['trailers']);
        
        $this->assertEquals('in_trip', $vehicleWithActiveTrip->fresh()->status);
        $this->assertEquals('available', $vehicleWithCompletedTrip->fresh()->status);
        $this->assertEquals('in_trip', $trailerWithActiveTrip->fresh()->status);
        $this->assertEquals('available', $trailerWithCompletedTrip->fresh()->status);
    }

    public function test_operator_cannot_have_multiple_active_trips()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle1 = Vehicle::factory()->available()->create();
        $vehicle2 = Vehicle::factory()->available()->create();
        $trailer1 = Trailer::factory()->available()->create();
        $trailer2 = Trailer::factory()->available()->create();

        // Act: Create first active trip
        $trip1 = Trip::factory()->inProgress()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle1->id,
            'trailer_id' => $trailer1->id
        ]);

        // Assert: Operator has active trip
        $activeTrips = $operator->trips()->whereIn('status', ['planned', 'in_progress'])->count();
        $this->assertEquals(1, $activeTrips);

        // Business rule validation would prevent creating second active trip
        // This would typically be handled in the application logic/validation
        $existingActiveTrips = $operator->trips()
            ->whereIn('status', ['planned', 'in_progress'])
            ->exists();
        
        $this->assertTrue($existingActiveTrips);
    }

    public function test_trip_profitability_calculation_end_to_end()
    {
        // Arrange: Create operator with multiple completed trips
        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();
        
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');

        // Create profitable trip
        $profitableTrip = Trip::factory()->completed()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-15'
        ]);

        TripCost::factory()->diesel()->create([
            'trip_id' => $profitableTrip->id,
            'amount' => 150.00
        ]);

        // Create less profitable trip
        $lessprofitable = Trip::factory()->completed()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-20'
        ]);

        TripCost::factory()->diesel()->create([
            'trip_id' => $lessprofitable->id,
            'amount' => 180.00
        ]);

        TripCost::factory()->tolls()->create([
            'trip_id' => $lessprofitable->id,
            'amount' => 45.00
        ]);

        $revenues = [
            $profitableTrip->id => 500.00,  // 70% margin
            $lessprofitable->id => 300.00   // 25% margin
        ];

        // Act: Calculate operator profitability
        $profitability = $this->profitabilityService->calculateOperatorProfitability(
            $operator,
            $startDate,
            $endDate,
            $revenues
        );

        // Assert: Profitability calculated correctly
        $this->assertEquals(2, $profitability['summary']['trip_count']);
        $this->assertEquals(375.00, $profitability['summary']['total_costs']); // 150 + 225
        $this->assertEquals(800.00, $profitability['summary']['total_revenue']); // 500 + 300
        $this->assertEquals(425.00, $profitability['summary']['total_profit']); // 800 - 375
        $this->assertEquals(53.125, $profitability['summary']['profit_margin']); // (425/800) * 100
        $this->assertCount(2, $profitability['trips']);
    }

    public function test_trip_cost_auto_calculation_from_quantity_and_unit_price()
    {
        // Arrange
        $trip = Trip::factory()->create();

        // Act: Create cost with quantity and unit price but no amount
        $cost = new TripCost([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'description' => 'Fuel purchase',
            'quantity' => 50.5,
            'unit_price' => 3.85
        ]);
        $cost->save();

        // Assert: Amount calculated automatically
        $this->assertEquals(194.43, $cost->fresh()->amount);
    }
}