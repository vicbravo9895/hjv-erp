<?php

namespace Database\Seeders;

use App\Models\WeeklyPayroll;
use App\Models\User;
use App\Models\Trip;
use App\Models\PaymentScale;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class WeeklyPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Obtener operadores activos
        $operators = User::activeOperators()->get();
        
        // Crear escalas de pago si no existen
        if (PaymentScale::count() === 0) {
            $this->createPaymentScales();
        }
        
        // Generar nóminas para las últimas 12 semanas
        $startDate = Carbon::now()->subWeeks(12)->startOfWeek();
        
        for ($week = 0; $week < 12; $week++) {
            $weekStart = $startDate->copy()->addWeeks($week);
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            foreach ($operators as $operator) {
                // Contar viajes completados en esa semana
                $tripsCount = Trip::where('operator_id', $operator->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$weekStart, $weekEnd])
                    ->count();
                
                // Calcular pago base (simulado)
                $basePayment = $this->calculateBasePayment($tripsCount, $faker);
                
                // Ajustes aleatorios (bonos, deducciones, etc.)
                $adjustments = $this->calculateAdjustments($faker, $basePayment);
                
                // Total
                $totalPayment = $basePayment + $adjustments;
                
                WeeklyPayroll::create([
                    'operator_id' => $operator->id,
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                    'trips_count' => $tripsCount,
                    'base_payment' => $basePayment,
                    'adjustments' => $adjustments,
                    'total_payment' => $totalPayment,
                ]);
            }
        }
    }
    
    private function createPaymentScales(): void
    {
        $scales = [
            ['trips_count' => 1, 'payment_amount' => 2500.00],
            ['trips_count' => 2, 'payment_amount' => 5200.00],
            ['trips_count' => 3, 'payment_amount' => 8100.00],
            ['trips_count' => 4, 'payment_amount' => 11200.00],
            ['trips_count' => 5, 'payment_amount' => 14500.00],
            ['trips_count' => 6, 'payment_amount' => 18000.00],
            ['trips_count' => 7, 'payment_amount' => 21700.00],
            ['trips_count' => 8, 'payment_amount' => 25600.00],
        ];
        
        foreach ($scales as $scale) {
            PaymentScale::create($scale);
        }
    }
    
    private function calculateBasePayment(int $tripsCount, $faker): float
    {
        // Pago base semanal
        $baseSalary = $faker->randomFloat(2, 2000, 4000);
        
        // Bono por viaje
        $tripBonus = $tripsCount * $faker->randomFloat(2, 150, 300);
        
        // Bono por kilómetros (simulado)
        $kmBonus = $tripsCount * $faker->randomFloat(2, 200, 800);
        
        return $baseSalary + $tripBonus + $kmBonus;
    }
    
    private function calculateAdjustments($faker, float $basePayment): float
    {
        $adjustments = 0;
        
        // 60% de probabilidad de tener bonos
        if ($faker->boolean(60)) {
            $bonusTypes = [
                'Bono de puntualidad' => $faker->randomFloat(2, 200, 500),
                'Bono de seguridad' => $faker->randomFloat(2, 300, 600),
                'Bono por eficiencia' => $faker->randomFloat(2, 150, 400),
                'Bono de fin de semana' => $faker->randomFloat(2, 250, 450),
            ];
            
            $selectedBonus = $faker->randomElement($bonusTypes);
            $adjustments += $selectedBonus;
        }
        
        // 30% de probabilidad de tener deducciones
        if ($faker->boolean(30)) {
            $deductionTypes = [
                'Descuento por combustible extra' => -$faker->randomFloat(2, 100, 300),
                'Descuento por daños menores' => -$faker->randomFloat(2, 200, 500),
                'Descuento por retraso' => -$faker->randomFloat(2, 150, 350),
                'Descuento por multa' => -$faker->randomFloat(2, 300, 800),
            ];
            
            $selectedDeduction = $faker->randomElement($deductionTypes);
            $adjustments += $selectedDeduction;
        }
        
        // 20% de probabilidad de adelanto de sueldo (deducción)
        if ($faker->boolean(20)) {
            $advance = -$faker->randomFloat(2, 500, $basePayment * 0.3);
            $adjustments += $advance;
        }
        
        return $adjustments;
    }
}