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
        // $request->user() nunca resolve neste projeto — a auth é 100% via
        // token Bearer + PL/pgSQL (AuthenticateMiddleware), sem guard nativo
        // do Laravel. Usar userObject() (o helper real deste projeto) em vez
        // de $request->user() em qualquer RateLimiter novo, senão o limite
        // degrada silenciosamente pra "por IP" pra todo mundo.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(userObject()?->id ?: $request->ip());
        });

        RateLimiter::for('organization-onboarding', function (Request $request) {
            $perHour = (int) SettingsEnum::getValue(SettingsEnum::ORGANIZATION_ONBOARDING_RATE_LIMIT_PER_HOUR);

            return Limit::perHour($perHour)->by(userObject()?->id ?: $request->ip());
        });

        RateLimiter::for('organization-member-lookup', function (Request $request) {
            $perHour = (int) SettingsEnum::getValue(SettingsEnum::ORGANIZATION_MEMBER_LOOKUP_RATE_LIMIT_PER_HOUR);

            return Limit::perHour($perHour)->by(userObject()?->id ?: $request->ip());
        });
    }
}
