# Prometheus Metrics Exporter for Laravel Cache

This package provides a Prometheus exporter specifically designed for Laravel's caching system. By intercepting standard Laravel cache events, it tracks and reports metrics such as operation latency, write volumes, and hit/miss frequencies for individual cache stores. The system relies entirely on APCu, ensuring that it introduces zero additional network or disk I/O to the instrumented requests.

## Prerequisites

- PHP 8.3 or higher
- Laravel 11.x or 12.x
- An active `apcu` PHP extension

If you plan to use CLI features (like the cache-exporter:reset command), you must also enable APCu for the CLI SAPI. Since this is typically disabled in standard php.ini files, you will need to add:

```ini

apc.enable_cli = 1

```

## Setup Guide

Install the package via Composer:

```bash

composer require mchardians/laravel-cache-exporter

```

To publish the configuration file to `config/cache-exporter.php`, run:

```bash

php artisan vendor:publish --tag=config

```

## Configuration Options

| Setting | Default Value | Purpose |
|---------|---------------|---------|

| `metrics_path` | `/metrics/cache` | The endpoint path for Prometheus scraping. It avoids the root /metrics path to prevent conflicts with other packages. |
| `excluded_key_patterns` | `['laravel_session']` | A list of substrings used to filter out specific cache keys. Any key containing these strings is ignored, which is helpful for excluding session traffic if SESSION_DRIVER and CACHE_STORE use the same backend. |
| `only_store` | `[]` | An explicit allowlist of cache stores to monitor. An empty array records all stores. This is beneficial during benchmarks to isolate specific engines and ignore internal package caches. |
| `allowed_ips` | `['127.0.0.1']` | A list of IP addresses authorized to access the metrics endpoint. Leaving this empty will block all incoming requests. |

## Usage

### Metric Scraping

Configure Prometheus to scrape the defined endpoint:

```yaml

scrape_configs:
  - job_name: 'laravel-cache'
    metrics_path: /metrics/cache
    static_configs:
      - targets: ['your-app-host:port']

```

Note that any request originating from an IP not listed in the allowed_ips configuration will be rejected with a 403 error.

### Clearing Data

Metrics are stored in APCu as continuously growing counters tied to a specific generation ID rather than being permanently deleted. To reset:

```bash

php artisan cache-exporter:reset

```

Executing this command bumps the generation ID forward. The previous generation's data stays in APCu but is hidden from the metrics endpoint, allowing you to start fresh for new benchmark runs without destroying historical raw data.

### Driver Switching

You do not need to modify the package configuration to measure a different cache engine. Every metric includes an engine label that is dynamically assigned based on the active store's resolved name when the cache event is triggered.

```bash

CACHE_STORE=redis

```

```bash

CACHE_STORE=memcached

```

Simply update your configuration, clear your cache if necessary, and subsequent cache operations will automatically be logged under the new store.

## Available Metrics

Every metric includes an engine label (file, redis, memcached, or unknown if unresolved) to identify the relevant cache store.

| Metric | Type | Description |
|--------|------|-------------|

| `laravel_cache_requests_total` | Counter | Tracks read attempts (the sum of hits and misses). Write operations are excluded from this metric. |
| `laravel_cache_hits_total` | Counter | Tracks successful read attempts where data was found. |
| `laravel_cache_misses_total` | Counter | Tracks failed read attempts where data was absent. |
| `laravel_cache_writes_total` | Counter | Tracks `put` (write) operations. |
| `laravel_cache_operation_duration_seconds_sum` | Counter | The total time consumed by all cache operations in seconds. |
| `laravel_cache_operation_duration_seconds_count` | Counter | The total number of timed operations, including both reads and writes. |
| `laravel_cache_operation_duration_squared_seconds_sum` | Counter | The aggregate sum of squared operational times. This allows for the calculation of variance and standard deviation without keeping raw samples. |

## Technical Implementation

This exporter operates by hooking into Laravel's native cache events (`RetrievingKey`, `CacheHit`, `CacheMissed`, `WritingKey`, `KeyWritten`) instead of wrapping the cache repository directly. This design choice ensures the package remains completely decoupled from specific store implementations. The store's identity is extracted directly from the event payload, meaning the same listener logic seamlessly handles Redis, Memcached, file, or any other supported driver.

All metric aggregations are performed in APCu using exclusively atomic increments (`apcu_inc`). This approach prevents race conditions and lost updates across concurrent PHP-FPM workers by avoiding a read-modify-write cycle. Furthermore, if APCu fails or is unavailable, the increment operations fail silently, ensuring the host application's core cache functionality is never disrupted by the exporter.

## Running Tests

```bash

composer install
php -d apc.enable_cli=1 ./vendor/bin/pest

```

Because APCu is disabled by default in the CLI SAPI (where the test suite executes), omitting the apc.enable_cli=1 flag will cause tests relying on actual APCu storage to be skipped. Therefore, a passing test suite without this flag is not a definitive indicator of success.

## Current Limitations

- **Single Node Scope**: APCu utilizes shared memory specific to a single machine. This exporter cannot aggregate metrics across multiple application servers sitting behind a load balancer.
- **IP Whitelisting Context**: The `allowed_ips` security feature relies on Laravel's `Request::ip`(), which depends on your trusted proxy settings. If your application operates behind a reverse proxy, ensure `TrustProxies` is properly configured before trusting the allowlist.
- **Traffic Filtering**: Traffic from other packages utilizing the same cache store is filtered solely by store name and key patterns. Unconventional store names or keys from third-party tools might still bypass the default exclusion rules.

## License

[MIT](./LICENSE)
