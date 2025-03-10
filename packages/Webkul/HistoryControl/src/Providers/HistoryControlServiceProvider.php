<?php

namespace Webkul\HistoryControl\Providers;

use Illuminate\Support\ServiceProvider;

class HistoryControlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the history control services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register the history control services.
     */
    public function register(): void {}
}
