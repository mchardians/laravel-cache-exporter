<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function() {
    skipIfApcuUnavailable();
});

test('no metrics are recorded and nothing throws when APCu is unavailable', function() {
    $this->bootAppWithApcuDisabled();

    Cache::store('array')->put('a', 1, 60);
    $value = Cache::store('array')->get('a');

    expect($value)->toBe(1);
    expect(apcu_fetch('cache_exporter:gen0:array:hits_total'))->toBeFalse();
    expect(apcu_fetch('cache_exporter:gen0:array:writes_total'))->toBeFalse();
});