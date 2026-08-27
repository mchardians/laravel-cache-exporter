<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    skipIfApcuUnavailable();
});

function currentGeneration(): int
{
    return (int) (apcu_fetch('cache_exporter:generation') ?: 0);
}

function metricKey(string $engine, string $suffix): string
{
    return 'cache_exporter:gen'.currentGeneration().":{$engine}:{$suffix}";
}

test('a cache hit is recorded with the correct engine label', function () {
    Cache::store('array')->put('greeting', 'hello', 60);
    Cache::store('array')->get('greeting');

    expect(apcu_fetch(metricKey('array', 'hits_total')))->toBe(1)
        ->and(apcu_fetch(metricKey('array', 'requests_total')))->toBe(1)
        ->and(apcu_fetch(metricKey('array', 'misses_total')))->toBeFalse();
});

test('a cache miss is recorded separately from hits', function () {
    Cache::store('array')->get('key-that-does-not-exist');

    expect(apcu_fetch(metricKey('array', 'misses_total')))->toBe(1)
        ->and(apcu_fetch(metricKey('array', 'requests_total')))->toBe(1)
        ->and(apcu_fetch(metricKey('array', 'hits_total')))->toBeFalse();
});

test('a cache write is recorded via the write counter, not the read counters', function () {
    Cache::store('array')->put('some-key', 'some-value', 60);

    expect(apcu_fetch(metricKey('array', 'writes_total')))->toBe(1)
        ->and(apcu_fetch(metricKey('array', 'hits_total')))->toBeFalse()
        ->and(apcu_fetch(metricKey('array', 'misses_total')))->toBeFalse();
});

test('listener instance stays consistent across the retrieving-then-hit event pair',
    function () {
        Cache::store('array')->getStore()->put('timed-key', 'value', 60);
        Cache::store('array')->get('timed-key');

        expect(apcu_fetch(metricKey('array', 'duration_count')))->toBe(1);
        expect(apcu_fetch(metricKey('array', 'duration_sum')))->toBeGreaterThanOrEqual(0);
    }
);

test('duration_sq_sum accumulates the square of the recorded duration', function () {
    Cache::store('array')->getStore()->put('timed-key', 'value', 60);
    Cache::store('array')->get('timed-key');

    $sum = apcu_fetch(metricKey('array', 'duration_sum'));
    $sqSum = apcu_fetch(metricKey('array', 'duration_sq_sum'));

    expect($sqSum)->toBe($sum * $sum);
});

test('keys matching excluded_key_patterns are not recorded at all', function () {
    config(['cache-exporter.excluded_key_patterns' => ['laravel_session']]);

    Cache::store('array')->put('laravel_session:abc123', 'x', 60);
    Cache::store('array')->get('laravel_session:abc123');

    expect(apcu_fetch(metricKey('array', 'requests_total')))->toBeFalse()
        ->and(apcu_fetch(metricKey('array', 'writes_total')))->toBeFalse();
});

test('only_store whitelist blocks stores not in the list', function () {
    config(['cache-exporter.only_store' => ['redis', 'memcached']]);

    Cache::store('array')->put('some-key', 'value', 60);
    Cache::store('array')->get('some-key');

    expect(apcu_fetch(metricKey('array', 'requests_total')))->toBeFalse();
});

test('only_store whitelist allows a listed store through', function () {
    config(['cache-exporter.only_store' => ['array']]);

    Cache::store('array')->put('some-key', 'value', 60);

    expect(apcu_fetch(metricKey('array', 'writes_total')))->toBe(1);
});

test('an empty only_store list means no restriction', function () {
    config(['cache-exporter.only_store' => []]);

    Cache::store('array')->put('some-key', 'value', 60);

    expect(apcu_fetch(metricKey('array', 'writes_total')))->toBe(1);
});