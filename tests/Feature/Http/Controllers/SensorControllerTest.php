<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Alert;
use App\Models\Measurement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SensorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_measurement()
    {
        $data = [
            'sensor' => '123e4567-e89b-12d3-a456-426614174000',
            'co2' => 1000,
            'time' => '2024-09-18T10:00:00+00:00'
        ];

        $response = $this->postJson('/api/v1/sensors/' . $data['sensor'] . '/measurements', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'sensor', 'co2', 'time', 'status']
            ]);

        $this->assertDatabaseHas('measurements', [
            'sensor' => $data['sensor'],
            'co2' => $data['co2'],
        ]);
    }

    public function test_get_sensor_status()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Measurement::factory()->create(['sensor' => $sensor, 'status' => 'OK']);

        $response = $this->getJson('/api/v1/sensors/' . $sensor);

        $response->assertStatus(200)
            ->assertJson(['status' => 'OK']);
    }

    public function test_get_sensor_metrics()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Measurement::factory()->count(5)->create(['sensor' => $sensor, 'co2' => 1000]);

        $response = $this->getJson('/api/v1/sensors/' . $sensor . '/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'maxLast30Days',
                'avgLast30Days'
            ]);
    }

    public function test_get_sensor_alerts()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Alert::factory()->count(3)->create(['sensor' => $sensor]);

        $response = $this->getJson('/api/v1/sensors/' . $sensor . '/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['startTime', 'endTime', 'measurement1', 'measurement2', 'measurement3']
            ]);
    }

    public function test_sensor_not_found()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';

        $response = $this->getJson('/api/v1/sensors/' . $sensor);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Sensor not found or not enough data']);
    }

    public function test_invalid_sensor_uuid()
    {
        $sensor = 'invalid-uuid';

        $response = $this->getJson('/api/v1/sensors/' . $sensor);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sensor']);
    }

    public function test_store_measurement_rate_limit()
    {
        $data = [
            'sensor' => '123e4567-e89b-12d3-a456-426614174000',
            'co2' => 1000,
            'time' => '2024-09-18T10:00:00+00:00'
        ];

        // First request should succeed
        $response = $this->postJson('/api/v1/sensors/' . $data['sensor'] . '/measurements', $data);
        $response->assertStatus(201);

        // Second request within a minute should fail
        $response = $this->postJson('/api/v1/sensors/' . $data['sensor'] . '/measurements', $data);
        $response->assertStatus(429)
            ->assertJson([
                'error' => 'Rate limit exceeded. Only one measurement per minute is allowed.'
            ]);

        // Travel in time
        $this->travel(1)->minutes();

        // After travelling, another request should succeed
        $response = $this->postJson('/api/v1/sensors/' . $data['sensor'] . '/measurements', $data);
        $response->assertStatus(201);
    }
}
