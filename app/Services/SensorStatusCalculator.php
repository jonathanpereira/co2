<?php

namespace App\Services;

use App\Repositories\AlertRepository;
use App\Repositories\MeasurementRepository;

class SensorStatusCalculator
{
    private const CO2_THRESHOLD = 2000;
    private const MEASUREMENTS_QTD_THRESHOLD = 3;

    public const STATUS_OK = 'OK';

    public const STATUS_WARN = 'WARN';

    public const STATUS_ALERT = 'ALERT';

    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly AlertRepository $alertRepository
    )
    {}

    public function calculateStatus(string $sensor, ?string $currentStatus = self::STATUS_OK): string
    {
        $latest3Measurements = $this->measurementRepository->get3LatestMeasurements($sensor);

        if ($latest3Measurements->count() < self::MEASUREMENTS_QTD_THRESHOLD) {
            return $latest3Measurements->first() < self::CO2_THRESHOLD ? self::STATUS_OK : self::STATUS_WARN;
        }

        $allBelowThreshold = $latest3Measurements->every(fn($co2Value) => $co2Value < self::CO2_THRESHOLD);
        $allAboveThreshold = $latest3Measurements->every(fn($co2Value) => $co2Value > self::CO2_THRESHOLD);

        if ($currentStatus === self::STATUS_ALERT) {
            if ($allBelowThreshold) {
                $this->alertRepository->endAlert($sensor);

                return self::STATUS_OK;
            }

            return self::STATUS_ALERT;
        }

        if ($allAboveThreshold) {
            $this->alertRepository->startAlert($sensor, $latest3Measurements);

            return self::STATUS_ALERT;
        }

        return $latest3Measurements->first() > self::CO2_THRESHOLD ? self::STATUS_WARN : self::STATUS_OK;
    }
}
