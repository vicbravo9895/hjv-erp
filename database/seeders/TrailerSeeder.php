<?php

namespace Database\Seeders;

use App\Models\Trailer;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TrailerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        $types = ['dry_van', 'refrigerated', 'flatbed', 'tanker'];
        $statuses = ['available', 'in_trip', 'maintenance', 'out_of_service'];
        
        $trailers = [
            [
                'external_id' => 'TR001',
                'name' => 'Trailer Alpha',
                'asset_number' => 'T001',
                'plate' => 'TRA-001',
                'type' => 'dry_van',
                'status' => 'available',
                'last_lat' => 19.4326,
                'last_lng' => -99.1332,
                'formatted_location' => 'Ciudad de México, CDMX',
                'last_location_at' => now()->subHours(1),
                'last_speed_mph' => 0,
                'last_heading_degrees' => 180.5,
                'synced_at' => now(),
            ],
            [
                'external_id' => 'TR002',
                'name' => 'Trailer Beta',
                'asset_number' => 'T002',
                'plate' => 'TRA-002',
                'type' => 'refrigerated',
                'status' => 'in_trip',
                'last_lat' => 20.6597,
                'last_lng' => -103.3496,
                'formatted_location' => 'Guadalajara, JAL',
                'last_location_at' => now()->subMinutes(15),
                'last_speed_mph' => 65,
                'last_heading_degrees' => 45.2,
                'synced_at' => now()->subMinutes(5),
            ],
            [
                'external_id' => 'TR003',
                'name' => 'Trailer Gamma',
                'asset_number' => 'T003',
                'plate' => 'TRA-003',
                'type' => 'flatbed',
                'status' => 'maintenance',
                'last_lat' => 25.6866,
                'last_lng' => -100.3161,
                'formatted_location' => 'Monterrey, NL',
                'last_location_at' => now()->subHours(6),
                'last_speed_mph' => 0,
                'last_heading_degrees' => 270.0,
                'synced_at' => now()->subHours(2),
            ],
        ];

        // Crear remolques predefinidos
        foreach ($trailers as $trailerData) {
            Trailer::create($trailerData);
        }

        // Crear remolques adicionales con datos aleatorios
        for ($i = 4; $i <= 20; $i++) {
            $assetNumber = "T" . str_pad($i, 3, '0', STR_PAD_LEFT);
            $status = $faker->randomElement($statuses);
            $isInTrip = $status === 'in_trip';
            
            Trailer::create([
                'external_id' => "TR{$assetNumber}",
                'name' => "Trailer {$faker->randomElement(['Delta', 'Echo', 'Foxtrot', 'Golf', 'Hotel', 'India', 'Juliet', 'Kilo', 'Lima', 'Mike'])}",
                'asset_number' => $assetNumber,
                'plate' => "TRA-{$assetNumber}",
                'type' => $faker->randomElement($types),
                'status' => $status,
                'last_lat' => $faker->latitude(14, 32),
                'last_lng' => $faker->longitude(-118, -86),
                'formatted_location' => $faker->city() . ', ' . $faker->stateAbbr(),
                'last_location_at' => $faker->dateTimeBetween('-1 day', 'now'),
                'last_speed_mph' => $isInTrip ? $faker->numberBetween(0, 75) : 0,
                'last_heading_degrees' => $faker->randomFloat(1, 0, 360),
                'synced_at' => $faker->dateTimeBetween('-2 hours', 'now'),
                'raw_snapshot' => [
                    'tire_pressure' => [
                        'front_left' => $faker->numberBetween(90, 110),
                        'front_right' => $faker->numberBetween(90, 110),
                        'rear_left' => $faker->numberBetween(90, 110),
                        'rear_right' => $faker->numberBetween(90, 110),
                    ],
                    'brake_temperature' => $faker->numberBetween(150, 300),
                    'cargo_weight' => $faker->numberBetween(0, 40000),
                ],
            ]);
        }
    }
}