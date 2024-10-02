<?php

namespace GhostZero\Kvdb;

use GhostZero\Kvdb\Console\Commands;
use Illuminate\Support\ServiceProvider as Base;

class ServiceProvider extends Base
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerObservers();
    }

    /**
     * Set up the configuration for KVDB.
     */
    private function configure(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/kvdb.php', 'kvdb');
        $this->loadMigrationsFrom(__DIR__ . '/../migrations/main');
    }

    /**
     * Offer publishing for the package.
     */
    private function offerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/kvdb.php' => config_path('kvdb.php')
        ], 'kvdb-config');

        $this->publishesMigrations([
            __DIR__ . '/../migrations/main/' => database_path('migrations'),
            __DIR__ . '/../migrations/kvdb/' => database_path('migrations/kvdb'),
        ], 'kvdb-migrations');
    }

    /**
     * Register the package's commands.
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MigrateCommand::class,
                Commands\InspectCommand::class,
            ]);
        }
    }

    /**
     * Register the package's routes.
     */
    private function registerRoutes(): void
    {
        if (!Kvdb::$ignoreRoutes) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/kvdb.php');
        }
    }

    /**
     * Register the package's observers.
     */
    private function registerObservers(): void
    {
        Models\Bucket::observe(Observers\BucketObserver::class);
    }
}