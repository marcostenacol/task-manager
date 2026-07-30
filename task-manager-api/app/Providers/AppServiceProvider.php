<?php

namespace App\Providers;

use App\Packages\Auth\Auth\Enum\SettingsEnum;
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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('organization-onboarding', function (Request $request) {
            $perHour = (int) SettingsEnum::getValue(SettingsEnum::ORGANIZATION_ONBOARDING_RATE_LIMIT_PER_HOUR);

            return Limit::perHour($perHour)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('organization-member-lookup', function (Request $request) {
            $perHour = (int) SettingsEnum::getValue(SettingsEnum::ORGANIZATION_MEMBER_LOOKUP_RATE_LIMIT_PER_HOUR);

            return Limit::perHour($perHour)->by($request->user()?->id ?: $request->ip());
        });
    }
}
