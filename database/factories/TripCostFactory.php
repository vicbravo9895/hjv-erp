<?php

namespace Database\Factories;

use App\Models\TripCost;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripCostFactory extends Factory
{
    protected $model = TripCost::class;

    public function definition(): array
    {
        $costType = $this->faker->randomElement([
            TripCost::TYPE_DIESEL,
            TripCost::TYPE_TOLLS,
            TripCost::TYPE_MANEUVERS,
            TripCost::TYPE_OTHER
        ]);

        return [
            'trip_id' => Trip::factory(),
            'cost_type' => $costType,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'description' => $this->getDescriptionForType($costType),
            'location' => $this->faker->city . ', ' . $this->faker->stateAbbr,
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_price' => $this->faker->randomFloat(2, 1, 10),
        ];
    }

    public function diesel(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => TripCost::TYPE_DIESEL,
            'description' => 'Diesel fuel purchase',
            'quantity' => $this->faker->randomFloat(2, 50, 200), // gallons
            'unit_price' => $this->faker->randomFloat(2, 3.50, 5.00), // per gallon
        ]);
    }

    public function tolls(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => TripCost::TYPE_TOLLS,
            'description' => 'Highway toll',
            'quantity' => 1,
            'unit_price' => $attributes['amount'],
        ]);
    }

    public function maneuvers(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => TripCost::TYPE_MANEUVERS,
            'description' => $this->faker->randomElement(['Loading', 'Unloading', 'Detention', 'Layover']),
            'quantity' => $this->faker->randomFloat(2, 1, 8), // hours
            'unit_price' => $this->faker->randomFloat(2, 25, 75), // per hour
        ]);
    }

    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => TripCost::TYPE_OTHER,
            'description' => $this->faker->randomElement(['Parking', 'Meals', 'Repairs', 'Permits']),
        ]);
    }

    private function getDescriptionForType(string $type): string
    {
        return match ($type) {
            TripCost::TYPE_DIESEL => 'Diesel fuel purchase',
            TripCost::TYPE_TOLLS => 'Highway toll',
            TripCost::TYPE_MANEUVERS => $this->faker->randomElement(['Loading', 'Unloading', 'Detention']),
            TripCost::TYPE_OTHER => $this->faker->randomElement(['Parking', 'Meals', 'Repairs']),
            default => 'Trip expense',
        };
    }
}