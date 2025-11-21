<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PinCheckerInterface;
use App\Services\PinCheckService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
