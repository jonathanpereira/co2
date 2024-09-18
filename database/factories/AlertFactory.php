<?php

namespace Database\Factories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'sensor' => $this->faker->uuid,
            'startTime' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'endTime' => $this->faker->optional(0.7)->dateTimeBetween('-1 day', 'now'),
            'measurement1' => $this->faker->numberBetween(2001, 3000),
            'measurement2' => $this->faker->numberBetween(2001, 3000),
            'measurement3' => $this->faker->numberBetween(2001, 3000),
        ];
    }

    public function ongoing(): AlertFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'endTime' => null,
            ];
        });
    }

    public function ended(): AlertFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'endTime' => $this->faker->dateTimeBetween($attributes['startTime'], 'now'),
            ];
        });
    }
}
