<?php

namespace Database\Seeders;

use App\Models\MaintenanceRecord;
use App\Models\SparePart;
use App\Models\Vehicle;
use App\Models\Trailer;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Crear refacciones primero
        $spareParts = [
            ['name' => 'Filtro de aceite', 'part_number' => 'FO-001', 'brand' => 'Mann Filter', 'unit_cost' => 250.00, 'stock_quantity' => 50, 'location' => 'A1-01'],
            ['name' => 'Filtro de aire', 'part_number' => 'FA-001', 'brand' => 'Donaldson', 'unit_cost' => 180.00, 'stock_quantity' => 30, 'location' => 'A1-02'],
            ['name' => 'Filtro de combustible', 'part_number' => 'FC-001', 'brand' => 'Fleetguard', 'unit_cost' => 320.00, 'stock_quantity' => 25, 'location' => 'A1-03'],
            ['name' => 'Pastillas de freno', 'part_number' => 'PF-001', 'brand' => 'Bendix', 'unit_cost' => 850.00, 'stock_quantity' => 40, 'location' => 'B2-01'],
            ['name' => 'Discos de freno', 'part_number' => 'DF-001', 'brand' => 'Bendix', 'unit_cost' => 1200.00, 'stock_quantity' => 20, 'location' => 'B2-02'],
            ['name' => 'Llanta 295/75R22.5', 'part_number' => 'LL-001', 'brand' => 'Michelin', 'unit_cost' => 4500.00, 'stock_quantity' => 15, 'location' => 'C3-01'],
            ['name' => 'Batería 12V', 'part_number' => 'BAT-001', 'brand' => 'Interstate', 'unit_cost' => 2800.00, 'stock_quantity' => 10, 'location' => 'D1-01'],
            ['name' => 'Correa del alternador', 'part_number' => 'CA-001', 'brand' => 'Gates', 'unit_cost' => 450.00, 'stock_quantity' => 35, 'location' => 'A2-01'],
            ['name' => 'Bujías de precalentamiento', 'part_number' => 'BP-001', 'brand' => 'Bosch', 'unit_cost' => 180.00, 'stock_quantity' => 60, 'location' => 'A2-02'],
            ['name' => 'Termostato', 'part_number' => 'TERM-001', 'brand' => 'Stant', 'unit_cost' => 380.00, 'stock_quantity' => 25, 'location' => 'A2-03'],
            ['name' => 'Bomba de agua', 'part_number' => 'BA-001', 'brand' => 'Gates', 'unit_cost' => 1800.00, 'stock_quantity' => 8, 'location' => 'B1-01'],
            ['name' => 'Radiador', 'part_number' => 'RAD-001', 'brand' => 'Spectra', 'unit_cost' => 5500.00, 'stock_quantity' => 5, 'location' => 'C1-01'],
            ['name' => 'Amortiguador delantero', 'part_number' => 'AD-001', 'brand' => 'Monroe', 'unit_cost' => 2200.00, 'stock_quantity' => 12, 'location' => 'B3-01'],
            ['name' => 'Amortiguador trasero', 'part_number' => 'AT-001', 'brand' => 'Monroe', 'unit_cost' => 1900.00, 'stock_quantity' => 15, 'location' => 'B3-02'],
            ['name' => 'Kit de embrague', 'part_number' => 'KE-001', 'brand' => 'Eaton', 'unit_cost' => 8500.00, 'stock_quantity' => 3, 'location' => 'C2-01'],
        ];
        
        foreach ($spareParts as $part) {
            SparePart::create($part);
        }
        
        // Obtener vehículos y remolques
        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $sparePartIds = SparePart::pluck('id')->toArray();
        
        $maintenanceTypes = [
            'preventivo',
            'correctivo',
            'emergencia',
            'inspeccion',
        ];
        
        // Crear registros de mantenimiento para vehículos
        foreach ($vehicles as $vehicle) {
            $numRecords = $faker->numberBetween(2, 8);
            
            for ($i = 0; $i < $numRecords; $i++) {
                $maintenanceType = $faker->randomElement($maintenanceTypes);
                $date = $faker->dateTimeBetween('-1 year', 'now');
                $cost = $this->generateMaintenanceCost($faker, $maintenanceType);
                
                $record = MaintenanceRecord::create([
                    'vehicle_id' => $vehicle->id,
                    'vehicle_type' => 'App\\Models\\Vehicle',
                    'maintenance_type' => $maintenanceType,
                    'date' => $date,
                    'cost' => $cost,
                    'description' => $this->generateMaintenanceDescription($faker, $maintenanceType),
                    'mechanic_id' => $faker->numberBetween(1, 5), // IDs de mecánicos ficticios
                ]);
                
                // Agregar refacciones usadas (30% de probabilidad)
                if ($faker->boolean(30)) {
                    $numParts = $faker->numberBetween(1, 4);
                    $selectedParts = $faker->randomElements($sparePartIds, $numParts);
                    
                    foreach ($selectedParts as $partId) {
                        $sparePart = SparePart::find($partId);
                        $quantityUsed = $faker->numberBetween(1, 3);
                        $partCost = $sparePart->unit_cost * $quantityUsed;
                        
                        $record->spareParts()->attach($partId, [
                            'quantity_used' => $quantityUsed,
                            'cost' => $partCost,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
        
        // Crear registros de mantenimiento para remolques
        foreach ($trailers as $trailer) {
            $numRecords = $faker->numberBetween(1, 4);
            
            for ($i = 0; $i < $numRecords; $i++) {
                $maintenanceType = $faker->randomElement($maintenanceTypes);
                $date = $faker->dateTimeBetween('-1 year', 'now');
                $cost = $this->generateMaintenanceCost($faker, $maintenanceType, true);
                
                $record = MaintenanceRecord::create([
                    'vehicle_id' => $trailer->id,
                    'vehicle_type' => 'App\\Models\\Trailer',
                    'maintenance_type' => $maintenanceType,
                    'date' => $date,
                    'cost' => $cost,
                    'description' => $this->generateTrailerMaintenanceDescription($faker, $maintenanceType),
                    'mechanic_id' => $faker->numberBetween(1, 5),
                ]);
                
                // Agregar refacciones para remolques (25% de probabilidad)
                if ($faker->boolean(25)) {
                    $numParts = $faker->numberBetween(1, 2);
                    $trailerParts = [1, 2, 4, 5, 6, 13, 14]; // IDs de refacciones aplicables a remolques
                    $selectedParts = $faker->randomElements($trailerParts, $numParts);
                    
                    foreach ($selectedParts as $partId) {
                        $sparePart = SparePart::find($partId);
                        $quantityUsed = $faker->numberBetween(1, 2);
                        $partCost = $sparePart->unit_cost * $quantityUsed;
                        
                        $record->spareParts()->attach($partId, [
                            'quantity_used' => $quantityUsed,
                            'cost' => $partCost,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
    
    private function generateMaintenanceCost($faker, $type, $isTrailer = false): float
    {
        $baseMultiplier = $isTrailer ? 0.6 : 1.0;
        
        switch ($type) {
            case 'preventivo':
                return $faker->randomFloat(2, 1500, 8000) * $baseMultiplier;
            case 'correctivo':
                return $faker->randomFloat(2, 3000, 15000) * $baseMultiplier;
            case 'emergencia':
                return $faker->randomFloat(2, 5000, 25000) * $baseMultiplier;
            case 'inspeccion':
                return $faker->randomFloat(2, 500, 2000) * $baseMultiplier;
            default:
                return $faker->randomFloat(2, 1000, 5000) * $baseMultiplier;
        }
    }
    
    private function generateMaintenanceDescription($faker, $type): string
    {
        $descriptions = [
            'preventivo' => [
                'Servicio preventivo 10,000 km',
                'Cambio de aceite y filtros',
                'Revisión general de sistemas',
                'Mantenimiento programado',
                'Inspección de frenos y suspensión',
            ],
            'correctivo' => [
                'Reparación de motor',
                'Cambio de transmisión',
                'Reparación del sistema de frenos',
                'Reparación eléctrica',
                'Cambio de embrague',
            ],
            'emergencia' => [
                'Reparación en carretera',
                'Falla mecánica urgente',
                'Reparación de emergencia',
                'Servicio de grúa y reparación',
                'Reparación nocturna urgente',
            ],
            'inspeccion' => [
                'Inspección anual obligatoria',
                'Verificación de emisiones',
                'Inspección de seguridad',
                'Revisión técnica vehicular',
                'Inspección pre-viaje',
            ],
        ];
        
        return $faker->randomElement($descriptions[$type] ?? ['Mantenimiento general']);
    }
    
    private function generateTrailerMaintenanceDescription($faker, $type): string
    {
        $descriptions = [
            'preventivo' => [
                'Servicio preventivo de remolque',
                'Lubricación de quinta rueda',
                'Revisión de sistema de frenos',
                'Inspección de llantas',
                'Mantenimiento de suspensión',
            ],
            'correctivo' => [
                'Reparación de frenos de remolque',
                'Cambio de llantas',
                'Reparación de suspensión',
                'Reparación de sistema eléctrico',
                'Reparación de piso de carga',
            ],
            'emergencia' => [
                'Reparación urgente en ruta',
                'Cambio de llanta en carretera',
                'Reparación de frenos urgente',
                'Servicio de emergencia',
            ],
            'inspeccion' => [
                'Inspección anual de remolque',
                'Verificación de seguridad',
                'Inspección de carga',
                'Revisión técnica',
            ],
        ];
        
        return $faker->randomElement($descriptions[$type] ?? ['Mantenimiento general de remolque']);
    }
}