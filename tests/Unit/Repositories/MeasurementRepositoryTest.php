<?php

namespace Tests\Unit\Repositories;

use App\Models\Measurement;
use App\Repositories\MeasurementRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MeasurementRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MeasurementRepository();
    }

    public function test_create_measurement()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        $co2 = 1000;
        $time = '2024-09-18T10:00:00+00:00';

        $measurement = $this->repository->create($sensor, $co2, $time);

        $this->assertInstanceOf(Measurement::class, $measurement);
        $this->assertEquals($sensor, $measurement->sensor);
        $this->assertEquals($co2, $measurement->co2);
        $this->assertEquals($time, $measurement->time);
    }

    public function test_get_status()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Measurement::factory()->create(['sensor' => $sensor, 'status' => 'OK']);

        $status = $this->repository->getStatus($sensor);

        $this->assertEquals('OK', $status);
    }

    public function test_get_metrics_keys()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Measurement::factory()->count(5)->create([
            'sensor' => $sensor,
            'co2' => 1000,
            'created_at' => now()->subDays(15)
        ]);

        $metrics = $this->repository->getMetrics($sensor);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('maxLast30Days', $metrics);
        $this->assertArrayHasKey('avgLast30Days', $metrics);
    }

    public function test_get_metrics_values()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        $fifteenDaysAgo = now()->subDays(15);

        $co2Values = [1000, 2000, 3000];

        foreach ($co2Values as $co2) {
            Measurement::factory()->create([
                'sensor' => $sensor,
                'co2' => $co2,
                'created_at' => $fifteenDaysAgo
            ]);
        }

        $metrics = $this->repository->getMetrics($sensor);

        $this->assertIsArray($metrics);
        $this->assertEquals(3000, $metrics['maxLast30Days']);
        $this->assertEquals(2000, $metrics['avgLast30Days']);
    }

    public function test_get_3_latest_measurements()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Measurement::factory()->count(5)->create(['sensor' => $sensor]);

        $measurements = $this->repository->get3LatestMeasurements($sensor);

        $this->assertCount(3, $measurements);
    }
}
