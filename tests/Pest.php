<?php

use Mchardians\LaravelCacheExporter\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function skipIfApcuUnavailable(): void {
    if(!function_exists('apcu_enabled') || !apcu_enabled()) {
        test()->markTestSkipped('APCu is not enabled for the CLI. Run with -d apc.enable_cli=1 to exercise this test.');
    }
}