<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    skipIfApcuUnavailable();
});

test('running the reset command advances the generation counter', function () {
    expect((int) (apcu_fetch('cache_exporter:generation') ?: 0))->toBe(0);

    $this->artisan('cache-exporter:reset')->assertSuccessful();

    expect((int) apcu_fetch('cache_exporter:generation'))->toBe(1);
});

test('running the reset command twice advances the counter each time', function () {
    $this->artisan('cache-exporter:reset')->assertSuccessful();
    $this->artisan('cache-exporter:reset')->assertSuccessful();

    expect((int) apcu_fetch('cache_exporter:generation'))->toBe(2);
});

test('metrics recorded before a reset are not visible under the new generation', function () {
    Cache::store('array')->put('a', 1, 60);
    Cache::store('array')->get('a');

    expect(apcu_fetch('cache_exporter:gen0:array:hits_total'))->toBe(1);

    $this->artisan('cache-exporter:reset')->assertSuccessful();

    expect(apcu_fetch('cache_exporter:gen0:array:hits_total'))->toBe(1)
        ->and(apcu_fetch('cache_exporter:gen1:array:hits_total'))->toBeFalse();
});