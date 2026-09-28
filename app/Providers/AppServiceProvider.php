<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Plan;
use App\Observers\PlanObserver;
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
        Schema::defaultStringLength(191);
        Plan::observe(PlanObserver::class);
        
        RateLimiter::for('usage', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->input('merchant_id') ?? $request->ip());
        });
    }
}
