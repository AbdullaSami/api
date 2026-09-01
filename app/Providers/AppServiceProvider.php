<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PinCheckerInterface;
use App\Services\PinCheckService;
use App\Services\LandingPageServiceInterface;
use App\Services\LandingPageService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PinCheckerInterface::class, function($app) {
        return new PinCheckService();
    });

    $this->app->bind(LandingPageServiceInterface::class, LandingPageService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
