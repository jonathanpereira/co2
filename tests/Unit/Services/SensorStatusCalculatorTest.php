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
    public function testLessThan3Measurements(array $measurements, string $expectedStatus): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection($measurements));

        $status = $this->calculator->calculateStatus('sensor1', 'OK');
        $this->assertSame($expectedStatus, $status);
    }

    public static function lessThan3MeasurementsProvider(): array
    {
        return [
            'Below threshold' => [[1500], 'OK'],
            'Above threshold' => [[2500], 'WARN'],
        ];
    }

    #[DataProvider('allMeasurementsBelowThresholdProvider')]
    public function testAllMeasurementsBelowThreshold(string $currentStatus, string $expectedStatus): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([1500, 1600, 1700]));

        if ($currentStatus === 'ALERT') {
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
            'From OK status' => ['OK', 'OK'],
            'From ALERT status' => ['ALERT', 'OK'],
        ];
    }

    public function testAllMeasurementsAboveThresholdFromOkStatus(): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([2500, 2600, 2700]));

        $this->alertRepository->shouldReceive('startAlert')
            ->once()
            ->with('sensor1', Mockery::type(Collection::class));

        $status = $this->calculator->calculateStatus('sensor1', 'OK');
        $this->assertSame('ALERT', $status);
    }

    public function testSomeMeasurementsAboveThresholdFromAlertStatus(): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([2500, 1600, 2700]));

        $status = $this->calculator->calculateStatus('sensor1', 'ALERT');
        $this->assertSame('ALERT', $status);
    }

    #[DataProvider('latestMeasurementProvider')]
    public function testLatestMeasurementThreshold(int $latestMeasurement, string $expectedStatus): void
    {
        $this->measurementRepository->shouldReceive('get3LatestMeasurements')
            ->once()
            ->andReturn(new Collection([$latestMeasurement, 1600, 1700]));

        $status = $this->calculator->calculateStatus('sensor1', 'OK');
        $this->assertSame($expectedStatus, $status);
    }

    public static function latestMeasurementProvider(): array
    {
        return [
            'Above threshold' => [2500, 'WARN'],
            'Below threshold' => [1500, 'OK'],
        ];
    }
}
