<?php

use App\Http\Controllers\SensorController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1/sensors')->group(function () {
    Route::get('/health', function () {
        return new JsonResponse(['status' => 'ok']);
    });

    Route::post('/{sensor}/measurements', [SensorController::class, 'store']);
    Route::get('/{sensor}', [SensorController::class, 'status']);
    Route::get('/{sensor}/metrics', [SensorController::class, 'metrics']);
    Route::get('/{sensor}/alerts', [SensorController::class, 'alerts']);
});

