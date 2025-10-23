<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\TripCost;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TripCostSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Obtener viajes completados y en progreso
        $trips = Trip::whereIn('status', ['completed', 'in_progress'])->get();
        
        // Ubicaciones comunes para gastos
        $fuelStations = [
            'Pemex Guadalajara Centro',
            'Shell Monterrey Norte',
            'BP Ciudad de México Sur',
            'Mobil Querétaro',
            'Pemex León Autopista',
            'Shell Veracruz Puerto',
            'BP Tijuana Frontera',
            'Pemex Mérida Centro',
        ];
        
        $tollLocations = [
            'Caseta México-Querétaro',
            'Caseta Guadalajara-Tepic',
            'Caseta Monterrey-Saltillo',
            'Caseta Puebla-Veracruz',
            'Caseta León-Aguascalientes',
            'Caseta Tijuana-Ensenada',
            'Caseta Cancún-Mérida',
        ];
        
        $maneuverLocations = [
            'Terminal CDMX Norte',
            'Puerto Veracruz',
            'Aduana Laredo',
            'Terminal Guadalajara',
            'Puerto Manzanillo',
            'Terminal Monterrey',
            'Aduana Tijuana',
        ];
        
        foreach ($trips as $trip) {
            // Número aleatorio de costos por viaje (1-8)
            $numCosts = $faker->numberBetween(1, 8);
            
            for ($i = 0; $i < $numCosts; $i++) {
                $costType = $faker->randomElement([
                    TripCost::TYPE_DIESEL,
                    TripCost::TYPE_TOLLS,
                    TripCost::TYPE_MANEUVERS,
                    TripCost::TYPE_OTHER
                ]);
                
                $costData = $this->generateCostData($faker, $costType, $fuelStations, $tollLocations, $maneuverLocations);
                
                TripCost::create([
                    'trip_id' => $trip->id,
                    'cost_type' => $costType,
                    'amount' => $costData['amount'],
                    'description' => $costData['description'],
                    'receipt_url' => $faker->boolean(30) ? $faker->url() : null, // 30% tienen recibo
                    'location' => $costData['location'],
                    'quantity' => $costData['quantity'],
                    'unit_price' => $costData['unit_price'],
                ]);
            }
        }
    }
    
    private function generateCostData($faker, $costType, $fuelStations, $tollLocations, $maneuverLocations): array
    {
        switch ($costType) {
            case TripCost::TYPE_DIESEL:
                $quantity = $faker->randomFloat(2, 50, 500); // litros
                $unitPrice = $faker->randomFloat(2, 20, 25); // precio por litro
                return [
                    'amount' => $quantity * $unitPrice,
                    'description' => "Carga de diésel - {$quantity}L",
                    'location' => $faker->randomElement($fuelStations),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
                
            case TripCost::TYPE_TOLLS:
                $amount = $faker->randomFloat(2, 50, 800);
                return [
                    'amount' => $amount,
                    'description' => 'Pago de peaje',
                    'location' => $faker->randomElement($tollLocations),
                    'quantity' => 1,
                    'unit_price' => $amount,
                ];
                
            case TripCost::TYPE_MANEUVERS:
                $amount = $faker->randomFloat(2, 200, 1500);
                return [
                    'amount' => $amount,
                    'description' => $faker->randomElement([
                        'Carga y descarga',
                        'Maniobras en terminal',
                        'Servicios portuarios',
                        'Trámites aduanales',
                        'Almacenaje temporal'
                    ]),
                    'location' => $faker->randomElement($maneuverLocations),
                    'quantity' => 1,
                    'unit_price' => $amount,
                ];
                
            case TripCost::TYPE_OTHER:
            default:
                $amount = $faker->randomFloat(2, 50, 500);
                return [
                    'amount' => $amount,
                    'description' => $faker->randomElement([
                        'Comida y hospedaje',
                        'Reparación menor',
                        'Lavado de unidad',
                        'Documentación',
                        'Comunicaciones',
                        'Estacionamiento'
                    ]),
                    'location' => $faker->city(),
                    'quantity' => 1,
                    'unit_price' => $amount,
                ];
        }
    }
}