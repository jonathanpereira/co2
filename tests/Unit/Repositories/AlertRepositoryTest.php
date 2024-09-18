<?php

namespace Tests\Unit\Repositories;

use App\Models\Alert;
use App\Repositories\AlertRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Collection;

class AlertRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AlertRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AlertRepository();
    }

    public function test_start_alert()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        $measurements = new Collection([2100, 2200, 2300]);

        $this->repository->startAlert($sensor, $measurements);

        $this->assertDatabaseHas('alerts', [
            'sensor' => $sensor,
            'measurement1' => 2300,
            'measurement2' => 2200,
            'measurement3' => 2100,
        ]);
    }

    public function test_end_alert()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Alert::factory()->create(['sensor' => $sensor, 'endTime' => null]);

        $this->repository->endAlert($sensor);

        $this->assertDatabaseHas('alerts', [
            'sensor' => $sensor,
            'endTime' => now(),
        ]);
    }

    public function test_get_alerts()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';
        Alert::factory()->count(3)->create(['sensor' => $sensor]);

        $alerts = $this->repository->getAlerts($sensor);

        $this->assertInstanceOf(Collection::class, $alerts);
        $this->assertCount(3, $alerts);
    }

    public function test_get_alerts_for_non_existent_sensor()
    {
        $sensor = '123e4567-e89b-12d3-a456-426614174000';

        $alerts = $this->repository->getAlerts($sensor);

        $this->assertNull($alerts);
    }
}
