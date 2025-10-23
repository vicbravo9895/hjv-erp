<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        // Obtener IDs disponibles
        $vehicleIds = Vehicle::pluck('id')->toArray();
        $trailerIds = Trailer::pluck('id')->toArray();
        $operatorIds = User::activeOperators()->pluck('id')->toArray();
        
        // Rutas comunes en México
        $routes = [
            ['origin' => 'Ciudad de México, CDMX', 'destination' => 'Guadalajara, JAL'],
            ['origin' => 'Ciudad de México, CDMX', 'destination' => 'Monterrey, NL'],
            ['origin' => 'Guadalajara, JAL', 'destination' => 'Tijuana, BC'],
            ['origin' => 'Monterrey, NL', 'destination' => 'Laredo, TX'],
            ['origin' => 'Ciudad de México, CDMX', 'destination' => 'Veracruz, VER'],
            ['origin' => 'Puebla, PUE', 'destination' => 'Mérida, YUC'],
            ['origin' => 'Querétaro, QRO', 'destination' => 'León, GTO'],
            ['origin' => 'Toluca, MEX', 'destination' => 'Morelia, MICH'],
            ['origin' => 'Aguascalientes, AGS', 'destination' => 'Zacatecas, ZAC'],
            ['origin' => 'Cancún, QROO', 'destination' => 'Ciudad de México, CDMX'],
        ];
        
        $statuses = ['planned', 'in_progress', 'completed', 'cancelled'];
        
        // Crear viajes de los últimos 3 meses
        for ($i = 0; $i < 150; $i++) {
            $route = $faker->randomElement($routes);
            $status = $faker->randomElement($statuses);
            $startDate = $faker->dateTimeBetween('-3 months', '+1 month');
            
            // Calcular fecha de fin basada en el estado
            $endDate = null;
            $completedAt = null;
            
            if ($status === 'completed') {
                $endDate = $faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
                $completedAt = $faker->dateTimeBetween($endDate, $endDate->format('Y-m-d') . ' +1 day');
            } elseif ($status === 'in_progress') {
                $endDate = $faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +5 days');
            } elseif ($status === 'planned') {
                $endDate = $faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
            } else { // cancelled
                $endDate = $faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +3 days');
            }
            
            Trip::create([
                'origin' => $route['origin'],
                'destination' => $route['destination'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'truck_id' => $faker->randomElement($vehicleIds),
                'trailer_id' => $faker->randomElement($trailerIds),
                'operator_id' => $faker->randomElement($operatorIds),
                'status' => $status,
                'completed_at' => $completedAt,
            ]);
        }
        
        // Crear algunos viajes específicos para demostración
        $demoTrips = [
            [
                'origin' => 'Ciudad de México, CDMX',
                'destination' => 'Guadalajara, JAL',
                'start_date' => now()->subDays(2),
                'end_date' => now()->addDays(1),
                'truck_id' => Vehicle::where('unit_number', '002')->first()->id,
                'trailer_id' => Trailer::where('asset_number', 'T002')->first()->id,
                'operator_id' => User::where('license_number', 'LIC001234567')->first()->id,
                'status' => 'in_progress',
                'completed_at' => null,
            ],
            [
                'origin' => 'Monterrey, NL',
                'destination' => 'Laredo, TX',
                'start_date' => now()->subDays(7),
                'end_date' => now()->subDays(5),
                'truck_id' => Vehicle::where('unit_number', '001')->first()->id,
                'trailer_id' => Trailer::where('asset_number', 'T001')->first()->id,
                'operator_id' => User::where('license_number', 'LIC002345678')->first()->id,
                'status' => 'completed',
                'completed_at' => now()->subDays(4),
            ],
            [
                'origin' => 'Querétaro, QRO',
                'destination' => 'León, GTO',
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(4),
                'truck_id' => Vehicle::where('unit_number', '001')->first()->id,
                'trailer_id' => Trailer::where('asset_number', 'T003')->first()->id,
                'operator_id' => User::where('license_number', 'LIC003456789')->first()->id,
                'status' => 'planned',
                'completed_at' => null,
            ],
        ];
        
        foreach ($demoTrips as $tripData) {
            Trip::create($tripData);
        }
    }
}