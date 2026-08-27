<?php

namespace Mchardians\LaravelCacheExporter\Http\Controllers;

use APCUIterator;
use Throwable;

class MetricsController
{
    private const METRICS = [
        'requests_total' => [
            'name' => 'laravel_cache_requests_total',
            'help' => 'Total number of cache access attempts',
            'type' => 'counter'
        ],
        'hits_total' => [
            'name' => 'laravel_cache_hits_total',
            'help' => 'Total number of requests that found data in the cache',
            'type' => 'counter'
        ],
        'misses_total' => [
            'name' => 'laravel_cache_misses_total',
            'help' => 'Total number of requests that missed and had to fetch raw data',
            'type' => 'counter'
        ],
        'writes_total' => [
            'name' => 'laravel_cache_writes_total',
            'help' => 'Total number of write (put) operations to the cache',
            'type' => 'counter'
        ],
        'duration_sum' => [
            'name' => 'laravel_cache_operation_duration_seconds_sum',
            'help' => 'Cumulative total time spent on cache operations',
            'type' => 'counter',
            'unit' => 'microseconds_to_seconds',
        ],
        'duration_count' => [
            'name' => 'laravel_cache_operation_duration_seconds_count',
            'help' => 'Number of cache operations with recorded timing',
            'type' => 'counter'
        ],
        'duration_sq_sum' => [
            'name' => 'laravel_cache_operation_duration_squared_seconds_sum',
            'help' => 'Cumulative sum of squared cache operation time (variance/stddev)',
            'type' => 'counter',
            'unit' => 'squared_microseconds_to_squared_seconds',
        ],
    ];

    public function __invoke()
    {
        $generation = $this->safeApcuFetch('cache_exporter:generation', 0);
        $engines = $this->discoverEngines($generation);
        sort($engines);

        $output = '';

        foreach(self::METRICS as $suffix => $meta) {
            $output .= "# HELP {$meta['name']} {$meta['help']}\n";
            $output .= "# TYPE {$meta['name']} {$meta['type']}\n";

            foreach($engines as $engine) {
                $key = "cache_exporter:gen{$generation}:{$engine}:{$suffix}";

                $raw = $this->safeApcuFetch($key, 0);

                $value = $this->applyUnit($raw, $meta['unit'] ?? null);

                $output .= sprintf(
                    "%s{engine=\"%s\"} %s\n",
                    $meta['name'],
                    $this->escapeLabelValue($engine),
                    $value
                );
            }

            $output .= "\n";
        }

        return response($output, 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function applyUnit(int $raw, ?string $unit): int|float
    {
        return match($unit) {
            'microseconds_to_seconds' => $raw / 1_000_000,
            'squared_microseconds_to_squared_seconds' => $raw / 1_000_000_000_000,
            default => $raw,
        };
    }

    private function escapeLabelValue(string $value): string
    {
        return str_replace(
            ['\\', '"', "\n"],
            ['\\\\', '\"', '\\n'],
            $value
        );
    }

    private function discoverEngines(int $generation): array
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return [];
        }
        
        $prefix = "cache_exporter:gen{$generation}:";
        $engines = [];

        try {
            foreach (new APCUIterator('/^' . preg_quote($prefix, '/') . '/') as $entry) {
                $suffix = substr($entry['key'], strlen($prefix));
                [$engine] = explode(':', $suffix, 2);
                $engines[$engine] = true;
            }
        } catch(Throwable) {
            return [];
        }
    
        return array_keys($engines);
    }

    private function safeApcuFetch(string $key, int $default): int
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return $default;
        }

        try {
            $value = apcu_fetch($key);

            return $value === false ? $default : (int) $value;
        } catch(Throwable) {
            return $default;
        }
    }
}