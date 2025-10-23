<?php

namespace Database\Factories;

use App\Models\Trailer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrailerFactory extends Factory
{
    protected $model = Trailer::class;

    public function definition(): array
    {
        return [
            'name' => 'Trailer ' . $this->faker->unique()->numberBetween(100, 999),
            'asset_number' => $this->faker->unique()->numerify('T####'),
            'plate' => strtoupper($this->faker->bothify('???-####')),
            'type' => $this->faker->randomElement(['dry_van', 'refrigerated', 'flatbed', 'tanker']),
            'status' => $this->faker->randomElement(['available', 'in_trip', 'maintenance', 'out_of_service']),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    public function inTrip(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_trip',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
        ]);
    }
}