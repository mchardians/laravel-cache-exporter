<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    skipIfApcuUnavailable();
});

test('the metrics endpoint responds with the correct Prometheus content type', function () {
    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
});

test('the metrics endpoint is never cached by an intermediary', function () {
    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $cacheControl = $response->headers->get('Cache-Control');

    expect($cacheControl)->toContain('no-store')
        ->and($cacheControl)->toContain('no-cache');
});

test('the output groups every series for one metric name together, not interleaved', function () {
    Cache::store('array')->put('a', 1, 60);
    Cache::store('array')->get('a');

    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));
    $body = $response->getContent();

    expect(substr_count($body, '# TYPE laravel_cache_hits_total'))->toBe(1);
});

test('duration metrics are exposed in seconds, not raw microseconds', function () {
    apcu_store('cache_exporter:gen0:array:duration_sum', 2_500_000);

    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));
    $body = $response->getContent();

    expect($body)->toContain('laravel_cache_operation_duration_seconds_sum{engine="array"} 2.5');
});

test('an engine with no recorded data does not appear as a label', function () {
    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));
    $body = $response->getContent();

    expect($body)->not->toContain('engine="redis"');
});

test('the endpoint still responds successfully with an empty dataset (post-reset state)', function () {
    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $response->assertOk();
    expect($response->getContent())->toContain('# HELP laravel_cache_requests_total');
});