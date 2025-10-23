<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        $makes = ['Freightliner', 'Peterbilt', 'Kenworth', 'Volvo', 'Mack', 'International'];
        $statuses = ['available', 'in_trip', 'maintenance', 'out_of_service'];
        
        $vehicles = [
            // Flota principal - vehículos disponibles
            [
                'external_id' => 'VH001',
                'vin' => '1FUJGBDV0CLBP1234',
                'serial_number' => 'SN001234',
                'name' => 'Truck Alpha',
                'unit_number' => '001',
                'plate' => 'ABC-123',
                'make' => 'Freightliner',
                'model' => 'Cascadia',
                'year' => 2022,
                'status' => 'available',
                'last_lat' => 19.4326,
                'last_lng' => -99.1332,
                'formatted_location' => 'Ciudad de México, CDMX',
                'last_location_at' => now()->subHours(2),
                'last_odometer_km' => 125000.50,
                'last_fuel_percent' => 85.5,
                'last_engine_state' => 'off',
                'last_speed_mph' => 0,
                'current_driver_external_id' => null,
                'current_driver_name' => null,
                'synced_at' => now(),
            ],
            [
                'external_id' => 'VH002',
                'vin' => '1FUJGBDV0CLBP5678',
                'serial_number' => 'SN005678',
                'name' => 'Truck Beta',
                'unit_number' => '002',
                'plate' => 'DEF-456',
                'make' => 'Peterbilt',
                'model' => '579',
                'year' => 2021,
                'status' => 'in_trip',
                'last_lat' => 20.6597,
                'last_lng' => -103.3496,
                'formatted_location' => 'Guadalajara, JAL',
                'last_location_at' => now()->subMinutes(30),
                'last_odometer_km' => 98750.25,
                'last_fuel_percent' => 45.2,
                'last_engine_state' => 'on',
                'last_speed_mph' => 65,
                'current_driver_external_id' => 'OP001',
                'current_driver_name' => 'Juan Pérez',
                'synced_at' => now()->subMinutes(5),
            ],
            [
                'external_id' => 'VH003',
                'vin' => '1FUJGBDV0CLBP9012',
                'serial_number' => 'SN009012',
                'name' => 'Truck Gamma',
                'unit_number' => '003',
                'plate' => 'GHI-789',
                'make' => 'Kenworth',
                'model' => 'T680',
                'year' => 2023,
                'status' => 'maintenance',
                'last_lat' => 25.6866,
                'last_lng' => -100.3161,
                'formatted_location' => 'Monterrey, NL',
                'last_location_at' => now()->subHours(8),
                'last_odometer_km' => 45200.75,
                'last_fuel_percent' => 92.8,
                'last_engine_state' => 'off',
                'last_speed_mph' => 0,
                'current_driver_external_id' => null,
                'current_driver_name' => null,
                'synced_at' => now()->subHours(1),
            ],
        ];

        // Crear vehículos predefinidos
        foreach ($vehicles as $vehicleData) {
            Vehicle::create($vehicleData);
        }

        // Crear vehículos adicionales con datos aleatorios
        for ($i = 4; $i <= 15; $i++) {
            $unitNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
            $status = $faker->randomElement($statuses);
            $isInTrip = $status === 'in_trip';
            
            Vehicle::create([
                'external_id' => "VH{$unitNumber}",
                'vin' => $faker->unique()->regexify('[A-Z0-9]{17}'),
                'serial_number' => "SN{$faker->unique()->numerify('######')}",
                'name' => "Truck {$faker->randomElement(['Delta', 'Echo', 'Foxtrot', 'Golf', 'Hotel', 'India', 'Juliet', 'Kilo', 'Lima', 'Mike', 'November', 'Oscar'])}",
                'unit_number' => $unitNumber,
                'plate' => $faker->regexify('[A-Z]{3}-[0-9]{3}'),
                'make' => $faker->randomElement($makes),
                'model' => $faker->randomElement(['Cascadia', '579', 'T680', 'VNL', 'Anthem', 'LT']),
                'year' => $faker->numberBetween(2018, 2024),
                'status' => $status,
                'last_lat' => $faker->latitude(14, 32), // México aproximadamente
                'last_lng' => $faker->longitude(-118, -86),
                'formatted_location' => $faker->city() . ', ' . $faker->stateAbbr(),
                'last_location_at' => $faker->dateTimeBetween('-1 day', 'now'),
                'last_odometer_km' => $faker->randomFloat(2, 50000, 200000),
                'last_fuel_percent' => $faker->randomFloat(1, 10, 100),
                'last_engine_state' => $isInTrip ? $faker->randomElement(['on', 'idle']) : 'off',
                'last_speed_mph' => $isInTrip ? $faker->numberBetween(0, 75) : 0,
                'current_driver_external_id' => $isInTrip ? "OP{$faker->numberBetween(1, 20)}" : null,
                'current_driver_name' => $isInTrip ? $faker->name() : null,
                'synced_at' => $faker->dateTimeBetween('-1 hour', 'now'),
                'raw_snapshot' => [
                    'engine_hours' => $faker->numberBetween(5000, 15000),
                    'engine_rpm' => $isInTrip ? $faker->numberBetween(800, 2000) : 0,
                    'coolant_temp' => $faker->numberBetween(180, 210),
                    'oil_pressure' => $faker->numberBetween(30, 80),
                ],
            ]);
        }
    }
}