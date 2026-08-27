<?php

use Illuminate\Support\Facades\Route;
use Mchardians\LaravelCacheExporter\Http\Controllers\MetricsController;

Route::get(config('cache-exporter.metrics_path', '/metrics/cache'), MetricsController::class)
->middleware('cache-exporter.restrict')
->name('cache-exporter.metrics');