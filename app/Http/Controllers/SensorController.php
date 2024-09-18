<?php

namespace App\Http\Controllers;

use App\Http\Requests\SensorRequest;
use App\Http\Requests\StoreMeasurementRequest;
use App\Repositories\AlertRepository;
use App\Repositories\MeasurementRepository;
use App\Services\SensorStatusCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class SensorController extends Controller
{
    private const MAX_ATTEMPTS_PER_MINUTE = 1;

    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly AlertRepository $alertRepository,
        private readonly SensorStatusCalculator $sensorStatusCalculator
    )
    {}

    public function store(StoreMeasurementRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $sensor = $validatedData['sensor'];

        // Check rate limit
        $executed = RateLimiter::attempt(
            'store-measurement:'.$sensor,
            self::MAX_ATTEMPTS_PER_MINUTE,
            function() use ($validatedData, $sensor) {
                return $this->storeMeasurement($validatedData, $sensor);
            }
        );

        if (!$executed) {
            return response()->json([
                'error' => 'Rate limit exceeded. Only one measurement per minute is allowed.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $executed;
    }

    private function storeMeasurement(array $validatedData, string $sensor): JsonResponse
    {
        try {
            $currentStatus = $this->measurementRepository->getStatus($sensor);

            $measurement = $this->measurementRepository->create(
                $sensor,
                $validatedData['co2'],
                $validatedData['time']
            );

            $status = $this->sensorStatusCalculator->calculateStatus($sensor, $currentStatus ?? 'OK');
            $measurement->update(['status' => $status]);

            return response()->json([
                'message' => 'Measurement stored successfully',
                'data' => $measurement
            ], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function status(SensorRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $status = $this->measurementRepository->getStatus($validatedData['sensor']);

        return $status ?
            response()->json(['status' => $status]) :
            response()->json(['message' => 'Sensor not found'], Response::HTTP_NOT_FOUND);
    }

    public function metrics(SensorRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $metrics = $this->measurementRepository->getMetrics($validatedData['sensor']);

        return $metrics ?
            response()->json($metrics) :
            response()->json(['message' => 'Sensor not found'], Response::HTTP_NOT_FOUND);
    }

    public function alerts(SensorRequest $request): JsonResponse
    {
        // TODO: it would be good to have a paginator here

        $validatedData = $request->validated();
        $alerts = $this->alertRepository->getAlerts($validatedData['sensor']);

        return $alerts ?
            response()->json($alerts) :
            response()->json(['message' => 'No alerts for the given sensor'], Response::HTTP_NOT_FOUND);
    }
}
