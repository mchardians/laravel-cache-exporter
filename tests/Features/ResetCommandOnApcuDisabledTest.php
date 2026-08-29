<?php

beforeEach(function() {
    skipIfApcuUnavailable();
});

test('the reset command fails gracefully when APCu is unavailable', function() {
    $this->bootAppWithApcuDisabled();

    $this->artisan('cache-exporter:reset')
        ->expectsOutputToContain('APCu is not enabled')
        ->assertFailed();
});