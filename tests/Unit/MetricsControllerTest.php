<?php

use Mchardians\LaravelCacheExporter\Http\Controllers\MetricsController;

test('escapeLabelValue escapes backslashes, double quotes, and newlines', function() {
    $controller = new MetricsController();
    $method = new ReflectionMethod($controller, 'escapeLabelValue');
    $method->setAccessible(true);

    $result = $method->invoke($controller, "back\\slash \"quoted\" new\nline");

    expect($result)
        ->not->toContain("\n")
        ->toContain('\\\\')
        ->toContain('\\"')
        ->toContain('\\n');
});