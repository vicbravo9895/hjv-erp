<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Truck',
            'unit_number' => $this->faker->unique()->numberBetween(100, 999),
            'plate' => strtoupper($this->faker->bothify('???-####')),
            'make' => $this->faker->randomElement(['Freightliner', 'Peterbilt', 'Kenworth', 'Volvo', 'Mack']),
            'model' => $this->faker->randomElement(['Cascadia', '579', 'T680', 'VNL', 'Anthem']),
            'year' => $this->faker->numberBetween(2015, 2024),
            'status' => $this->faker->randomElement(['available', 'in_trip', 'maintenance', 'out_of_service']),
            'vin' => strtoupper($this->faker->bothify('?#?#?#?#?#?#?#?#?')),
            'serial_number' => $this->faker->unique()->numerify('SN######'),
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

    public function outOfService(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'out_of_service',
        ]);
    }
}