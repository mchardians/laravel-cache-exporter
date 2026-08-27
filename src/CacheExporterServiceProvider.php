<?php

namespace Mchardians\LaravelCacheExporter;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Mchardians\LaravelCacheExporter\Console\Commands\ResetMetricsCommand;
use Mchardians\LaravelCacheExporter\Http\Middleware\RestrictToObserverIp;
use Mchardians\LaravelCacheExporter\Listeners\CacheEventSubscriber;

class CacheExporterServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void {
        $this->publishes([
            __DIR__.'/../config/cache-exporter.php' => config_path('cache-exporter.php'),
        ], 'config');

        Event::subscribe(CacheEventSubscriber::class);

        $router->aliasMiddleware('cache-exporter.restrict', RestrictToObserverIp::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if($this->app->runningInConsole()) {
            $this->commands([
                ResetMetricsCommand::class
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cache-exporter.php', 'cache-exporter');
    }
}