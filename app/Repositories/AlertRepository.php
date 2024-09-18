<?php

namespace App\Repositories;

use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertRepository
{
    private const MEASUREMENTS_QTD_THRESHOLD = 3;

    public function startAlert(string $sensor, Collection $measurements): void
    {
        if ($measurements->count() < self::MEASUREMENTS_QTD_THRESHOLD) {
            Log::error('Not enough measurements to start alert');
            return;
        }

        Alert::create([
            'sensor' => $sensor,
            'measurement1' => $measurements[2],
            'measurement2' => $measurements[1],
            'measurement3' => $measurements[0],
            'startTime' => Carbon::now(),
        ]);
    }

    public function endAlert(string $sensor): void
    {
        $alert = Alert::where('sensor', $sensor)
            ->latest()
            ->first();

        if (!$alert) {
            Log::error('Alert not found');
            return;
        }

        $alert->update([
            'endTime' => Carbon::now()
        ]);
    }

    public function getAlerts(string $sensor): ?Collection
    {
        $sensorExists = Alert::where('sensor', $sensor)->exists();

        if (!$sensorExists) {
            return null;
        }

        return Alert::where('sensor', $sensor)
            ->select(
                DB::raw("DATE_FORMAT(startTime, '%Y-%m-%dT%TZ') as startTime"),
                DB::raw("DATE_FORMAT(endTime, '%Y-%m-%dT%TZ') as endTime"),
                'measurement1',
                'measurement2',
                'measurement3'
            )
            ->orderByDesc('id')
            ->get();
    }
}
