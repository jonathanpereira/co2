<?php

namespace Tests\Unit\Services;

use App\Repositories\AlertRepository;
use App\Repositories\MeasurementRepository;
use App\Services\SensorStatusCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Mockery;

class SensorStatusCalculatorTest extends TestCase
{
    private MeasurementRepository $measurementRepository;
    private AlertRepository $alertRepository;
    private SensorStatusCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->measurementRepository = Mockery::mock(MeasurementRepository::class);
        $this->alertRepository = Mockery::mock(AlertRepository::class);
        $this->calculator = new SensorStatusCalculator($this->measurementRepository, $this->alertRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[DataProvider('lessThan3MeasurementsProvider')]
    public function test_less_than_3_measurements(array $measurements, string $expectedStatus): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection($measurements));

        $status = $this->calculator->calculateStatus('sensor1', SensorStatusCalculator::STATUS_OK);
        $this->assertSame($expectedStatus, $status);
    }

    public static function lessThan3MeasurementsProvider(): array
    {
        return [
            'Below threshold' => [[1500], SensorStatusCalculator::STATUS_OK],
            'Above threshold' => [[2500], SensorStatusCalculator::STATUS_WARN],
        ];
    }

    #[DataProvider('allMeasurementsBelowThresholdProvider')]
    public function test_all_measurements_below_threshold(string $currentStatus, string $expectedStatus): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([1500, 1600, 1700]));

        if ($currentStatus === SensorStatusCalculator::STATUS_ALERT) {
            $this->alertRepository->shouldReceive('endAlert')
                ->once()
                ->with('sensor1');
        }

        $status = $this->calculator->calculateStatus('sensor1', $currentStatus);
        $this->assertSame($expectedStatus, $status);
    }

    public static function allMeasurementsBelowThresholdProvider(): array
    {
        return [
            'From OK status' => [SensorStatusCalculator::STATUS_OK, SensorStatusCalculator::STATUS_OK],
            'From ALERT status' => [SensorStatusCalculator::STATUS_ALERT, SensorStatusCalculator::STATUS_OK],
        ];
    }

    public function test_all_measurements_above_threshold_from_ok_status(): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([2500, 2600, 2700]));

        $this->alertRepository->shouldReceive('startAlert')
            ->once()
            ->with('sensor1', Mockery::type(Collection::class));

        $status = $this->calculator->calculateStatus('sensor1', SensorStatusCalculator::STATUS_OK);
        $this->assertSame(SensorStatusCalculator::STATUS_ALERT, $status);
    }

    public function test_some_measurements_above_threshold_from_alert_status(): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([2500, 1600, 2700]));

        $status = $this->calculator->calculateStatus('sensor1', SensorStatusCalculator::STATUS_ALERT);
        $this->assertSame(SensorStatusCalculator::STATUS_ALERT, $status);
    }

    #[DataProvider('latestMeasurementProvider')]
    public function test_latest_measurement_threshold(int $latestMeasurement, string $expectedStatus): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([$latestMeasurement, 1600, 1700]));

        $status = $this->calculator->calculateStatus('sensor1', SensorStatusCalculator::STATUS_OK);
        $this->assertSame($expectedStatus, $status);
    }

    public static function latestMeasurementProvider(): array
    {
        return [
            'Above threshold' => [2500, SensorStatusCalculator::STATUS_WARN],
            'Below threshold' => [1500, SensorStatusCalculator::STATUS_OK],
        ];
    }
}
