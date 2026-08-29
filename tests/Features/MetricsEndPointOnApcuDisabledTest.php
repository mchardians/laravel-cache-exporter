<?php

beforeEach(function() {
    skipIfApcuUnavailable();
});

test('the metrics endpoint still responds successfully when APCu is unavailable', function() {
    $this->bootAppWithApcuDisabled();

    $response = $this->get(config('cache-exporter.metrics_path', '/metrics/cache'));

    $response->assertOk();

    expect($response->getContent())->toContain('# HELP laravel_cache_requests_total');
});