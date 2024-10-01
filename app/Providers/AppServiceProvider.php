<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('sensor_measurements', function (Request $request) {
            return Limit::perMinute(1)->by($request->sensor)->response(
                function (Request $request, array $headers) {
                    return response()->json(
                        ['error' => 'Rate limit exceeded. Only one measurement per minute is allowed.'],
                        429,
                        $headers
                    );
            });
        });
    }
}
