<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('recommendations', function (Request $request) {
            $limit = max(1, (int) config('services.recommendation.rate_limit_per_minute', 5));

            return Limit::perMinute($limit)
                ->by((string) $request->user()->getAuthIdentifier())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Bạn đã yêu cầu gợi ý quá nhanh. Vui lòng chờ một phút rồi thử lại.',
                ], 429, $headers));
        });
    }
}
