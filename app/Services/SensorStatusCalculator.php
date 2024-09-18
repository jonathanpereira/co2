<?php

namespace App\Services;

use App\Repositories\AlertRepository;
use App\Repositories\MeasurementRepository;

class SensorStatusCalculator
{
    private const CO2_THRESHOLD = 2000;
    private const MEASUREMENTS_QTD_THRESHOLD = 3;

    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly AlertRepository $alertRepository
    )
    {}

    public function calculateStatus(string $sensor, ?string $currentStatus = 'OK'): string
    {
        $latest3Measurements = $this->measurementRepository->get3LatestMeasurements($sensor);

        if ($latest3Measurements->count() < self::MEASUREMENTS_QTD_THRESHOLD) {
            return $latest3Measurements->first() < self::CO2_THRESHOLD ? 'OK' : 'WARN';
        }

        $allBelowThreshold = $latest3Measurements->every(fn($co2Value) => $co2Value < self::CO2_THRESHOLD);
        $allAboveThreshold = $latest3Measurements->every(fn($co2Value) => $co2Value > self::CO2_THRESHOLD);

        if ($currentStatus === 'ALERT') {
            if ($allBelowThreshold) {
                $this->alertRepository->endAlert($sensor);

                return 'OK';
            }

            return 'ALERT';
        }

        if ($allAboveThreshold) {
            $this->alertRepository->startAlert($sensor, $latest3Measurements);

            return 'ALERT';
        }

        return $latest3Measurements->first() > self::CO2_THRESHOLD ? 'WARN' : 'OK';
    }
}
