<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 week');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');

        return [
            'origin' => $this->faker->city . ', ' . $this->faker->stateAbbr,
            'destination' => $this->faker->city . ', ' . $this->faker->stateAbbr,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'truck_id' => Vehicle::factory(),
            'trailer_id' => Trailer::factory(),
            'operator_id' => User::factory()->state(['role' => 'operador']),
            'status' => $this->faker->randomElement(['planned', 'in_progress', 'completed', 'cancelled']),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $attributes['start_date'] ?? $this->faker->dateTimeBetween('-1 month', '-1 week');
            $completedAt = $this->faker->dateTimeBetween($startDate, 'now');
            
            return [
                'status' => 'completed',
                'completed_at' => $completedAt,
            ];
        });
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}