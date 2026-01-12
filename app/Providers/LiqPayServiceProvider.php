<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Service\LiqPayService;

class LiqPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LiqPayService::class, function ($app) {
            return new LiqPayService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
