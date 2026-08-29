<?php

namespace Mchardians\LaravelCacheExporter\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Mchardians\LaravelCacheExporter\CacheExporterServiceProvider;
use Override;

class TestCase extends Orchestra 
{
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            CacheExporterServiceProvider::class
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);
        $app['config']->set('cache-exporter.excluded_key_patterns', ['laravel_session']);
        $app['config']->set('cache-exporter.only_store', []);
        $app['config']->set('cache-exporter.allowed_ips', ['127.0.0.1']);
    }

    #[Override]
    protected function setUp(): void
    {
        if(function_exists('apcu_enabled') && apcu_enabled()) {
            apcu_clear_cache();
        }

        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        Support\ApcuAvailabilityStub::$forceDisabled = false;
        
        parent::tearDown();
    }

    protected function bootAppWithApcuDisabled(): void
    {
        Support\ApcuAvailabilityStub::$forceDisabled = true;

        $this->refreshApplication();
    }
}