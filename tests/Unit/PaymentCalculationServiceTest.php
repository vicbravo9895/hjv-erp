<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PaymentCalculationService;
use App\Services\WeeklyTripCountService;
use App\Models\PaymentScale;
use App\Models\WeeklyPayroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class PaymentCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentCalculationService $service;
    protected $mockTripCountService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockTripCountService = Mockery::mock(WeeklyTripCountService::class);
        $this->service = new PaymentCalculationService($this->mockTripCountService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_base_payment_with_exact_match()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 7, 'payment_amount' => 1400.00]);

        // Act
        $payment = $this->service->calculateBasePayment(6);

        // Assert
        $this->assertEquals(1200.00, $payment);
    }

    public function test_calculate_base_payment_with_closest_match()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 10, 'payment_amount' => 1800.00]);

        // Act - 8 trips should get payment for 6 trips (closest lower)
        $payment = $this->service->calculateBasePayment(8);

        // Assert
        $this->assertEquals(1200.00, $payment);
    }

    public function test_calculate_base_payment_with_no_scale_returns_zero()
    {
        // Act
        $payment = $this->service->calculateBasePayment(5);

        // Assert
        $this->assertEquals(0, $payment);
    }

    public function test_calculate_total_payment_with_positive_adjustment()
    {
        // Act
        $total = $this->service->calculateTotalPayment(1200.00, 100.00);

        // Assert
        $this->assertEquals(1300.00, $total);
    }

    public function test_calculate_total_payment_with_negative_adjustment()
    {
        // Act
        $total = $this->service->calculateTotalPayment(1200.00, -50.00);

        // Assert
        $this->assertEquals(1150.00, $total);
    }

    public function test_calculate_total_payment_with_no_adjustment()
    {
        // Act
        $total = $this->service->calculateTotalPayment(1200.00);

        // Assert
        $this->assertEquals(1200.00, $total);
    }

    public function test_calculate_weekly_payroll_creates_new_record()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $weekStart = Carbon::parse('2024-01-01'); // Monday
        $weekEnd = Carbon::parse('2024-01-07'); // Sunday
        
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        
        $this->mockTripCountService
            ->shouldReceive('getWeekEnd')
            ->with($weekStart)
            ->andReturn($weekEnd);
            
        $this->mockTripCountService
            ->shouldReceive('countTripsForWeek')
            ->with($operator->id, $weekStart)
            ->andReturn(6);

        // Act
        $payroll = $this->service->calculateWeeklyPayroll($operator->id, $weekStart, 50.00);

        // Assert
        $this->assertInstanceOf(WeeklyPayroll::class, $payroll);
        $this->assertEquals($operator->id, $payroll->operator_id);
        $this->assertEquals('2024-01-01', $payroll->week_start->format('Y-m-d'));
        $this->assertEquals('2024-01-07', $payroll->week_end->format('Y-m-d'));
        $this->assertEquals(6, $payroll->trips_count);
        $this->assertEquals(1200.00, $payroll->base_payment);
        $this->assertEquals(50.00, $payroll->adjustments);
        $this->assertEquals(1250.00, $payroll->total_payment);
    }

    public function test_calculate_weekly_payroll_updates_existing_record()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $weekStart = Carbon::parse('2024-01-01');
        $weekEnd = Carbon::parse('2024-01-07');
        
        // Create existing payroll
        $existingPayroll = WeeklyPayroll::create([
            'operator_id' => $operator->id,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'trips_count' => 5,
            'base_payment' => 1000.00,
            'adjustments' => 0,
            'total_payment' => 1000.00,
        ]);
        
        PaymentScale::create(['trips_count' => 7, 'payment_amount' => 1400.00]);
        
        $this->mockTripCountService
            ->shouldReceive('getWeekEnd')
            ->with($weekStart)
            ->andReturn($weekEnd);
            
        $this->mockTripCountService
            ->shouldReceive('countTripsForWeek')
            ->with($operator->id, $weekStart)
            ->andReturn(7);

        // Act
        $payroll = $this->service->calculateWeeklyPayroll($operator->id, $weekStart);

        // Assert
        $this->assertEquals($existingPayroll->id, $payroll->id);
        $this->assertEquals(7, $payroll->trips_count);
        $this->assertEquals(1400.00, $payroll->base_payment);
        $this->assertEquals(0, $payroll->adjustments);
        $this->assertEquals(1400.00, $payroll->total_payment);
    }

    public function test_apply_adjustment_adds_to_existing_adjustment()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $payroll = WeeklyPayroll::create([
            'operator_id' => $operator->id,
            'week_start' => '2024-01-01',
            'week_end' => '2024-01-07',
            'trips_count' => 6,
            'base_payment' => 1200.00,
            'adjustments' => 50.00,
            'total_payment' => 1250.00,
        ]);

        // Act
        $updatedPayroll = $this->service->applyAdjustment($payroll, 25.00);

        // Assert
        $this->assertEquals(75.00, $updatedPayroll->adjustments);
        $this->assertEquals(1275.00, $updatedPayroll->total_payment);
    }

    public function test_set_adjustment_replaces_existing_adjustment()
    {
        // Arrange
        $operator = User::factory()->state(['role' => 'operador'])->create();
        $payroll = WeeklyPayroll::create([
            'operator_id' => $operator->id,
            'week_start' => '2024-01-01',
            'week_end' => '2024-01-07',
            'trips_count' => 6,
            'base_payment' => 1200.00,
            'adjustments' => 50.00,
            'total_payment' => 1250.00,
        ]);

        // Act
        $updatedPayroll = $this->service->setAdjustment($payroll, 100.00);

        // Assert
        $this->assertEquals(100.00, $updatedPayroll->adjustments);
        $this->assertEquals(1300.00, $updatedPayroll->total_payment);
    }

    public function test_has_payment_scale_for_trips_returns_true_when_exists()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);

        // Act & Assert
        $this->assertTrue($this->service->hasPaymentScaleForTrips(6));
    }

    public function test_has_payment_scale_for_trips_returns_false_when_not_exists()
    {
        // Act & Assert
        $this->assertFalse($this->service->hasPaymentScaleForTrips(10));
    }

    public function test_get_payment_scales_returns_ordered_collection()
    {
        // Arrange
        PaymentScale::create(['trips_count' => 10, 'payment_amount' => 1800.00]);
        PaymentScale::create(['trips_count' => 6, 'payment_amount' => 1200.00]);
        PaymentScale::create(['trips_count' => 8, 'payment_amount' => 1500.00]);

        // Act
        $scales = $this->service->getPaymentScales();

        // Assert
        $this->assertCount(3, $scales);
        $this->assertEquals(6, $scales->first()->trips_count);
        $this->assertEquals(10, $scales->last()->trips_count);
    }
}