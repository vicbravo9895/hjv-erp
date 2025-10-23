<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\Trip;
use App\Models\PaymentScale;
use App\Models\WeeklyPayroll;
use App\Services\PaymentCalculationService;
use App\Services\WeeklyTripCountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentCalculationService $paymentService;
    protected WeeklyTripCountService $tripCountService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tripCountService = new WeeklyTripCountService();
        $this->paymentService = new PaymentCalculationService($this->tripCountService);
    }

    public function test_end_to_end_payroll_calculation_for_operator()
    {
        // Arrange: Set up payment scales
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 7, 'payment_amount' => 1400.00]);
        PaymentScale::create(['trips_count' => 8, 'payment_amount' => 1600.00]);

        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();
        $trailer = Trailer::factory()->create();

        $weekStart = Carbon::parse('2024-01-01'); // Monday
        $weekEnd = Carbon::parse('2024-01-07');   // Sunday

        // Act: Create completed trips for the week
        for ($i = 0; $i < 7; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => $trailer->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays($i),
                'end_date' => $weekStart->copy()->addDays($i)->addDay(),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays($i)->addHours(8)
            ]);
        }

        // Act: Calculate weekly payroll
        $payroll = $this->paymentService->calculateWeeklyPayroll($operator->id, $weekStart);

        // Assert: Payroll calculated correctly for 7 trips
        $this->assertEquals($operator->id, $payroll->operator_id);
        $this->assertEquals('2024-01-01', $payroll->week_start->format('Y-m-d'));
        $this->assertEquals('2024-01-07', $payroll->week_end->format('Y-m-d'));
        $this->assertEquals(7, $payroll->trips_count);
        $this->assertEquals(1400.00, $payroll->base_payment);
        $this->assertEquals(0, $payroll->adjustments);
        $this->assertEquals(1400.00, $payroll->total_payment);

        // Assert: Payroll record saved to database
        $this->assertDatabaseHas('weekly_payrolls', [
            'operator_id' => $operator->id,
            'week_start' => '2024-01-01',
            'trips_count' => 7,
            'base_payment' => 1400.00,
            'total_payment' => 1400.00
        ]);
    }

    public function test_payroll_calculation_with_partial_trips()
    {
        // Arrange: Payment scale for different trip counts
        PaymentScale::create(['trips_count' => 4, 'payment_amount' => 1000.00]);
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 8, 'payment_amount' => 1600.00]);

        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();

        $weekStart = Carbon::parse('2024-01-08'); // Monday

        // Act: Create only 5 completed trips (should get payment for closest lower scale)
        for ($i = 0; $i < 5; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays($i),
                'end_date' => $weekStart->copy()->addDays($i)->addDay(),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays($i)->addHours(6)
            ]);
        }

        // Act: Calculate payroll
        $payroll = $this->paymentService->calculateWeeklyPayroll($operator->id, $weekStart);

        // Assert: Should get payment for 4 trips (closest lower scale)
        $this->assertEquals(5, $payroll->trips_count);
        $this->assertEquals(1000.00, $payroll->base_payment); // Gets 4-trip payment for 5 trips
        $this->assertEquals(1000.00, $payroll->total_payment);
    }

    public function test_payroll_calculation_with_manual_adjustments()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);

        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();

        $weekStart = Carbon::parse('2024-01-15');

        // Create 6 completed trips
        for ($i = 0; $i < 6; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays($i),
                'end_date' => $weekStart->copy()->addDays($i)->addDay(),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays($i)->addHours(8)
            ]);
        }

        // Act: Calculate payroll with adjustment
        $payroll = $this->paymentService->calculateWeeklyPayroll($operator->id, $weekStart, 150.00);

        // Assert: Adjustment applied correctly
        $this->assertEquals(6, $payroll->trips_count);
        $this->assertEquals(1200.00, $payroll->base_payment);
        $this->assertEquals(150.00, $payroll->adjustments);
        $this->assertEquals(1350.00, $payroll->total_payment);

        // Act: Apply additional adjustment
        $updatedPayroll = $this->paymentService->applyAdjustment($payroll, 50.00);

        // Assert: Additional adjustment added
        $this->assertEquals(200.00, $updatedPayroll->adjustments);
        $this->assertEquals(1400.00, $updatedPayroll->total_payment);

        // Act: Set new adjustment (replace existing)
        $finalPayroll = $this->paymentService->setAdjustment($updatedPayroll, 75.00);

        // Assert: Adjustment replaced
        $this->assertEquals(75.00, $finalPayroll->adjustments);
        $this->assertEquals(1275.00, $finalPayroll->total_payment);
    }

    public function test_payroll_calculation_for_multiple_operators()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 8, 'payment_amount' => 1600.00]);

        $operator1 = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $operator2 = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();

        $weekStart = Carbon::parse('2024-01-22');

        // Create trips for operator 1 (6 trips)
        for ($i = 0; $i < 6; $i++) {
            Trip::create([
                'operator_id' => $operator1->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays($i),
                'end_date' => $weekStart->copy()->addDays($i)->addDay(),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays($i)->addHours(8)
            ]);
        }

        // Create trips for operator 2 (8 trips)
        for ($i = 0; $i < 8; $i++) {
            Trip::create([
                'operator_id' => $operator2->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays($i % 7), // Some on same days
                'end_date' => $weekStart->copy()->addDays($i % 7)->addDays(2),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays($i % 7)->addHours(10 + $i)
            ]);
        }

        // Act: Calculate payroll for all operators
        $payrolls = $this->paymentService->calculateWeeklyPayrollForAllOperators($weekStart);

        // Assert: Both operators processed
        $this->assertCount(2, $payrolls);

        $operator1Payroll = $payrolls->firstWhere('operator_id', $operator1->id);
        $operator2Payroll = $payrolls->firstWhere('operator_id', $operator2->id);

        $this->assertEquals(6, $operator1Payroll->trips_count);
        $this->assertEquals(1200.00, $operator1Payroll->base_payment);

        $this->assertEquals(8, $operator2Payroll->trips_count);
        $this->assertEquals(1600.00, $operator2Payroll->base_payment);
    }

    public function test_payroll_summary_calculation()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);

        $operator1 = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $operator2 = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();

        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');

        // Create payrolls for different weeks
        WeeklyPayroll::create([
            'operator_id' => $operator1->id,
            'week_start' => '2024-01-01',
            'week_end' => '2024-01-07',
            'trips_count' => 6,
            'base_payment' => 1200.00,
            'adjustments' => 50.00,
            'total_payment' => 1250.00
        ]);

        WeeklyPayroll::create([
            'operator_id' => $operator1->id,
            'week_start' => '2024-01-08',
            'week_end' => '2024-01-14',
            'trips_count' => 7,
            'base_payment' => 1400.00,
            'adjustments' => -25.00,
            'total_payment' => 1375.00
        ]);

        WeeklyPayroll::create([
            'operator_id' => $operator2->id,
            'week_start' => '2024-01-01',
            'week_end' => '2024-01-07',
            'trips_count' => 5,
            'base_payment' => 1200.00,
            'adjustments' => 0,
            'total_payment' => 1200.00
        ]);

        // Act: Calculate summary
        $summary = $this->paymentService->calculatePayrollSummary($startDate, $endDate);

        // Assert: Summary calculated correctly
        $this->assertEquals(3825.00, $summary['total_payments']); // 1250 + 1375 + 1200
        $this->assertEquals(3800.00, $summary['total_base_payments']); // 1200 + 1400 + 1200
        $this->assertEquals(25.00, $summary['total_adjustments']); // 50 - 25 + 0
        $this->assertEquals(18, $summary['total_trips']); // 6 + 7 + 5
        $this->assertEquals(2, $summary['operator_count']);
        $this->assertEquals(1912.50, $summary['average_payment_per_operator']); // 3825 / 2
        $this->assertCount(3, $summary['payrolls']);
    }

    public function test_payroll_recalculation_after_trip_changes()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 7, 'payment_amount' => 1400.00]);

        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();

        $weekStart = Carbon::parse('2024-02-05');

        // Create initial payroll with 6 trips
        for ($i = 0; $i < 6; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays($i),
                'end_date' => $weekStart->copy()->addDays($i)->addDay(),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays($i)->addHours(8)
            ]);
        }

        $initialPayroll = $this->paymentService->calculateWeeklyPayroll($operator->id, $weekStart, 100.00);

        // Assert: Initial payroll
        $this->assertEquals(6, $initialPayroll->trips_count);
        $this->assertEquals(1200.00, $initialPayroll->base_payment);
        $this->assertEquals(1300.00, $initialPayroll->total_payment);

        // Act: Add one more completed trip
        Trip::create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'trailer_id' => Trailer::factory()->create()->id,
            'origin' => 'Los Angeles, CA',
            'destination' => 'Phoenix, AZ',
            'start_date' => $weekStart->copy()->addDay(),
            'end_date' => $weekStart->copy()->addDays(2),
            'status' => 'completed',
            'completed_at' => $weekStart->copy()->addDay()->addHours(8)
        ]);

        // Act: Recalculate payroll
        $recalculatedPayroll = $this->paymentService->recalculatePayroll($initialPayroll);

        // Assert: Payroll updated with new trip count and payment
        $this->assertEquals(7, $recalculatedPayroll->trips_count);
        $this->assertEquals(1400.00, $recalculatedPayroll->base_payment);
        $this->assertEquals(1500.00, $recalculatedPayroll->total_payment); // 1400 + 100 adjustment
        $this->assertEquals(100.00, $recalculatedPayroll->adjustments); // Adjustment preserved
    }

    public function test_payroll_calculation_excludes_non_completed_trips()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 4, 'payment_amount' => 1000.00]);
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);

        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();

        $weekStart = Carbon::parse('2024-02-12');

        // Create mix of completed and non-completed trips
        for ($i = 0; $i < 4; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays(1),
                'end_date' => $weekStart->copy()->addDays(2),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays(1)->addHours(8 + $i)
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays(2),
                'end_date' => $weekStart->copy()->addDays(3),
                'status' => 'in_progress'
            ]);
        }

        Trip::create([
            'operator_id' => $operator->id,
            'truck_id' => $vehicle->id,
            'trailer_id' => Trailer::factory()->create()->id,
            'origin' => 'Los Angeles, CA',
            'destination' => 'Phoenix, AZ',
            'start_date' => $weekStart->copy()->addDays(3),
            'end_date' => $weekStart->copy()->addDays(4),
            'status' => 'planned'
        ]);

        // Act: Calculate payroll
        $payroll = $this->paymentService->calculateWeeklyPayroll($operator->id, $weekStart);

        // Assert: Only completed trips counted
        $this->assertEquals(4, $payroll->trips_count);
        $this->assertEquals(1000.00, $payroll->base_payment); // Gets 4-trip payment for 4 completed trips
    }

    public function test_payment_breakdown_provides_detailed_information()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);

        $operator = User::factory()->state(['role' => 'operador', 'status' => 'active'])->create();
        $vehicle = Vehicle::factory()->create();

        $weekStart = Carbon::parse('2024-02-19');

        // Create trips
        for ($i = 0; $i < 6; $i++) {
            Trip::create([
                'operator_id' => $operator->id,
                'truck_id' => $vehicle->id,
                'trailer_id' => Trailer::factory()->create()->id,
                'origin' => 'Los Angeles, CA',
                'destination' => 'Phoenix, AZ',
                'start_date' => $weekStart->copy()->addDays(1),
                'end_date' => $weekStart->copy()->addDays(2),
                'status' => 'completed',
                'completed_at' => $weekStart->copy()->addDays(1)->addHours(8 + $i)
            ]);
        }

        // Create existing payroll with adjustment
        $payroll = $this->paymentService->calculateWeeklyPayroll($operator->id, $weekStart, 75.00);

        // Act: Get payment breakdown
        $breakdown = $this->paymentService->getPaymentBreakdown($operator->id, $weekStart);

        // Assert: Breakdown contains all necessary information
        $this->assertEquals($operator->id, $breakdown['operator_id']);
        $this->assertEquals($weekStart, $breakdown['week_start']);
        $this->assertEquals(6, $breakdown['trips_count']);
        $this->assertEquals(1200.00, $breakdown['base_payment']);
        $this->assertEquals(75.00, $breakdown['adjustments']);
        $this->assertEquals(1275.00, $breakdown['total_payment']);
        $this->assertCount(6, $breakdown['trips']);
        $this->assertTrue($breakdown['payroll_exists']);
    }
}