<?php

beforeEach(function () {
    skipIfApcuUnavailable();
});

test('a request from an allowed IP can reach the metrics endpoint', function () {
    config(['cache-exporter.allowed_ips' => ['127.0.0.1']]);

    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $response->assertOk();
});

test('a request from an IP outside the allow list is blocked with 403', function () {
    config(['cache-exporter.allowed_ips' => ['127.0.0.1']]);

    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
        ->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $response->assertForbidden();
});

test('an empty allow list blocks every request', function () {
    config(['cache-exporter.allowed_ips' => []]);

    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $response->assertForbidden();
});