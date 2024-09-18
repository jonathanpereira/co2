<?php

namespace App\Repositories;

use App\Models\Measurement;
use Carbon\Carbon;
use Exception;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeasurementRepository
{
    /**
     * @throws Exception
     */
    public function create(string $sensor, int $co2, string $time): Measurement
    {
        DB::beginTransaction();
        try {
            $measurement = Measurement::create([
                'sensor' => $sensor,
                'co2' => $co2,
                'time' => $time
            ]);

            DB::commit();
            return $measurement;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error creating measurement: " . $e->getMessage(), [
                'sensor' => $sensor,
                'co2' => $co2,
                'time' => $time,
            ]);
            throw $e;
        }
    }

    public function getStatus(string $sensor): ?string
    {
        return Measurement::where('sensor', $sensor)
            ->latest()
            ->value('status');
    }

    public function getMetrics(string $sensor): ?array
    {
        $sensorExists = Measurement::where('sensor', $sensor)->exists();

        if (!$sensorExists) {
            return null;
        }

        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $stats = Measurement::where('sensor', $sensor)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('MAX(co2) AS maxLast30Days, AVG(co2) AS avgLast30Days')
            ->first();

        return [
            'maxLast30Days' => (int) $stats->maxLast30Days,
            'avgLast30Days' => round((float) $stats->avgLast30Days, 2),
        ];
    }

    public function get3LatestMeasurements(string $sensor): Collection
    {
        return Measurement::where('sensor', $sensor)
            ->latest()
            ->take(3)
            ->pluck('co2');
    }
}
