<?php

namespace Database\Factories;

use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeasurementFactory extends Factory
{
    protected $model = Measurement::class;

    public function definition(): array
    {
        return [
            'sensor' => $this->faker->uuid,
            'co2' => $this->faker->numberBetween(400, 2500),
            'time' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d\TH:i:sP'),
            'status' => $this->faker->randomElement(['OK', 'WARN', 'ALERT']),
        ];
    }

    public function ok(): MeasurementFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'co2' => $this->faker->numberBetween(400, 1999),
                'status' => 'OK',
            ];
        });
    }

    public function warn(): MeasurementFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'co2' => $this->faker->numberBetween(2000, 2500),
                'status' => 'WARN',
            ];
        });
    }

    public function alert(): MeasurementFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'co2' => $this->faker->numberBetween(2001, 3000),
                'status' => 'ALERT',
            ];
        });
    }
}
