<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProfitabilityService;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ProfitabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProfitabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProfitabilityService();
    }

    public function test_calculate_trip_profitability_without_revenue()
    {
        // Arrange
        $trip = Trip::factory()->create();
        TripCost::factory()->create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 150.00
        ]);
        TripCost::factory()->create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_TOLLS,
            'amount' => 50.00
        ]);

        // Act
        $profitability = $this->service->calculateTripProfitability($trip);

        // Assert
        $this->assertEquals($trip->id, $profitability['trip_id']);
        $this->assertEquals(200.00, $profitability['total_costs']);
        $this->assertEquals(150.00, $profitability['cost_breakdown']['diesel']);
        $this->assertEquals(50.00, $profitability['cost_breakdown']['tolls']);
        $this->assertEquals(0, $profitability['cost_breakdown']['maneuvers']);
        $this->assertEquals(0, $profitability['cost_breakdown']['other']);
        $this->assertNull($profitability['revenue']);
        $this->assertNull($profitability['profit']);
        $this->assertNull($profitability['profit_margin']);
    }

    public function test_calculate_trip_profitability_with_revenue()
    {
        // Arrange
        $trip = Trip::factory()->create();
        TripCost::factory()->create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 150.00
        ]);
        TripCost::factory()->create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_MANEUVERS,
            'amount' => 75.00
        ]);

        // Act
        $profitability = $this->service->calculateTripProfitability($trip, 500.00);

        // Assert
        $this->assertEquals(225.00, $profitability['total_costs']);
        $this->assertEquals(500.00, $profitability['revenue']);
        $this->assertEquals(275.00, $profitability['profit']);
        $this->assertEquals(55.0, round($profitability['profit_margin'], 1)); // (275/500) * 100
    }

    public function test_calculate_trip_profitability_with_zero_revenue()
    {
        // Arrange
        $trip = Trip::factory()->create();
        TripCost::factory()->create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 100.00
        ]);

        // Act
        $profitability = $this->service->calculateTripProfitability($trip, 0);

        // Assert
        $this->assertEquals(0, $profitability['revenue']);
        $this->assertEquals(-100.00, $profitability['profit']);
        $this->assertNull($profitability['profit_margin']);
    }

    public function test_calculate_operator_profitability()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $vehicle = Vehicle::factory()->create();
        
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        
        // Create completed trips
        $trip1 = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-15',
            'status' => 'completed'
        ]);
        
        $trip2 = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-20',
            'status' => 'completed'
        ]);

        // Add costs to trips
        TripCost::factory()->create([
            'trip_id' => $trip1->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 100.00
        ]);
        
        TripCost::factory()->create([
            'trip_id' => $trip2->id,
            'cost_type' => TripCost::TYPE_TOLLS,
            'amount' => 50.00
        ]);

        $revenues = [$trip1->id => 300.00, $trip2->id => 200.00];

        // Act
        $profitability = $this->service->calculateOperatorProfitability(
            $operator, 
            $startDate, 
            $endDate, 
            $revenues
        );

        // Assert
        $this->assertEquals($operator->id, $profitability['operator_id']);
        $this->assertEquals($operator->name, $profitability['operator_name']);
        $this->assertEquals(2, $profitability['summary']['trip_count']);
        $this->assertEquals(150.00, $profitability['summary']['total_costs']);
        $this->assertEquals(500.00, $profitability['summary']['total_revenue']);
        $this->assertEquals(350.00, $profitability['summary']['total_profit']);
        $this->assertEquals(70.0, $profitability['summary']['profit_margin']);
        $this->assertEquals(75.00, $profitability['summary']['average_cost_per_trip']);
        $this->assertEquals(250.00, $profitability['summary']['average_revenue_per_trip']);
        $this->assertCount(2, $profitability['trips']);
    }

    public function test_calculate_vehicle_profitability()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $vehicle = Vehicle::factory()->create();
        
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        
        $trip = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-15',
            'status' => 'completed'
        ]);

        TripCost::factory()->create([
            'trip_id' => $trip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 120.00
        ]);

        $revenues = [$trip->id => 400.00];

        // Act
        $profitability = $this->service->calculateVehicleProfitability(
            $vehicle, 
            $startDate, 
            $endDate, 
            $revenues
        );

        // Assert
        $this->assertEquals($vehicle->id, $profitability['vehicle_id']);
        $this->assertEquals($vehicle->display_name, $profitability['vehicle_name']);
        $this->assertEquals(1, $profitability['summary']['trip_count']);
        $this->assertEquals(120.00, $profitability['summary']['total_costs']);
        $this->assertEquals(400.00, $profitability['summary']['total_revenue']);
        $this->assertEquals(280.00, $profitability['summary']['total_profit']);
        $this->assertEquals(70.0, $profitability['summary']['profit_margin']);
    }

    public function test_get_overall_profitability_stats()
    {
        // Arrange
        $operator1 = User::factory()->state(['role' => 'operador'])->create();
        $operator2 = User::factory()->state(['role' => 'operador'])->create();
        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();
        
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        
        $trip1 = Trip::factory()->create([
            'operator_id' => $operator1->id,
            'truck_id' => $vehicle1->id,
            'start_date' => '2024-01-15',
            'status' => 'completed'
        ]);
        
        $trip2 = Trip::factory()->create([
            'operator_id' => $operator2->id,
            'truck_id' => $vehicle2->id,
            'start_date' => '2024-01-20',
            'status' => 'completed'
        ]);

        TripCost::factory()->create([
            'trip_id' => $trip1->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 100.00
        ]);
        
        TripCost::factory()->create([
            'trip_id' => $trip2->id,
            'cost_type' => TripCost::TYPE_TOLLS,
            'amount' => 80.00
        ]);

        $revenues = [$trip1->id => 350.00, $trip2->id => 300.00];

        // Act
        $stats = $this->service->getOverallProfitabilityStats($startDate, $endDate, $revenues);

        // Assert
        $this->assertEquals(2, $stats['overall_summary']['trip_count']);
        $this->assertEquals(180.00, $stats['overall_summary']['total_costs']);
        $this->assertEquals(650.00, $stats['overall_summary']['total_revenue']);
        $this->assertEquals(470.00, $stats['overall_summary']['total_profit']);
        $this->assertEquals(90.00, $stats['overall_summary']['average_cost_per_trip']);
        $this->assertEquals(325.00, $stats['overall_summary']['average_revenue_per_trip']);
        $this->assertCount(2, $stats['operator_stats']);
        $this->assertCount(2, $stats['vehicle_stats']);
    }

    public function test_get_profitability_ranking()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $vehicle = Vehicle::factory()->create();
        
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-31');
        
        // Create trips with different profitability
        $profitableTrip = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-15',
            'status' => 'completed'
        ]);
        
        $unprofitableTrip = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'start_date' => '2024-01-20',
            'status' => 'completed'
        ]);

        TripCost::factory()->create([
            'trip_id' => $profitableTrip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 100.00
        ]);
        
        TripCost::factory()->create([
            'trip_id' => $unprofitableTrip->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 180.00
        ]);

        $revenues = [
            $profitableTrip->id => 500.00,    // 80% margin
            $unprofitableTrip->id => 200.00   // 10% margin
        ];

        // Act
        $ranking = $this->service->getProfitabilityRanking($startDate, $endDate, $revenues, 5);

        // Assert
        $this->assertCount(2, $ranking['most_profitable']);
        $this->assertCount(2, $ranking['least_profitable']);
        
        // Most profitable should be first (80% margin)
        $this->assertEquals($profitableTrip->id, $ranking['most_profitable'][0]['trip_id']);
        $this->assertEquals(80.0, $ranking['most_profitable'][0]['profit_margin']);
        
        // Least profitable should be first (10% margin)
        $this->assertEquals($unprofitableTrip->id, $ranking['least_profitable'][0]['trip_id']);
        $this->assertEquals(10.0, $ranking['least_profitable'][0]['profit_margin']);
    }

    public function test_calculate_multiple_trips_profitability()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $vehicle = Vehicle::factory()->create();
        
        $trip1 = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
        ]);
        
        $trip2 = Trip::factory()->create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
        ]);

        TripCost::factory()->create([
            'trip_id' => $trip1->id,
            'cost_type' => TripCost::TYPE_DIESEL,
            'amount' => 100.00
        ]);
        
        TripCost::factory()->create([
            'trip_id' => $trip2->id,
            'cost_type' => TripCost::TYPE_TOLLS,
            'amount' => 50.00
        ]);

        $trips = collect([$trip1, $trip2]);
        $revenues = [$trip1->id => 300.00, $trip2->id => 150.00];

        // Act
        $results = $this->service->calculateMultipleTripsProfitability($trips, $revenues);

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals($trip1->id, $results[0]['trip_id']);
        $this->assertEquals(100.00, $results[0]['total_costs']);
        $this->assertEquals(300.00, $results[0]['revenue']);
        $this->assertEquals(200.00, $results[0]['profit']);
        
        $this->assertEquals($trip2->id, $results[1]['trip_id']);
        $this->assertEquals(50.00, $results[1]['total_costs']);
        $this->assertEquals(150.00, $results[1]['revenue']);
        $this->assertEquals(100.00, $results[1]['profit']);
    }
}