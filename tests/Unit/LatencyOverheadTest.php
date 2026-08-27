<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    skipIfApcuUnavailable();
});

test('a single cache get-hit cycle stays under the 0.5ms overhead budget', function () {
    Cache::store('array')->put('warm-key', 'value', 60);

    Cache::store('array')->get('warm-key');

    $iterations = 200;
    $start = hrtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        Cache::store('array')->get('warm-key');
    }

    $elapsedMicroseconds = (hrtime(true) - $start) / 1000;
    $averagePerOperation = $elapsedMicroseconds / $iterations;

    expect($averagePerOperation)->toBeLessThan(500);
})->group('performance');