<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Metrics Path
    |--------------------------------------------------------------------------
    |
    | The path used for the Prometheus scrape endpoint. The default is
    | intentionally scoped to a specific path (/metrics/cache) rather than
    | the generic /metrics path. This avoids assuming exclusive ownership
    | of the /metrics root path in the host application, as other packages
    | or exporters may need to use it in the future.
    |
    | Override this value via .env if there is no path conflict.
    |
    */
    'metrics_path' => env('CACHE_EXPORTER_METRICS_PATH', '/metrics/cache'),

    /*
    |--------------------------------------------------------------------------
    | Only Store (Engine Whitelist)
    |--------------------------------------------------------------------------
    |
    | Optional whitelist of store names (matching the "engine" label,
    | i.e. the resolved value of the event's storeName, or "unknown"
    | when storeName is null) that the exporter is allowed to record.
    | Leave empty to record every store with no restriction. Use this
    | as an extra safety net on top of excluded_key_patterns — e.g. if
    | some other store (like "array") becomes active unintentionally,
    | its events are silently dropped instead of polluting the dataset.
    |
    | Example: ['file', 'redis', 'memcached']
    |
    */
    'only_store' => [],
    
    /*
    |--------------------------------------------------------------------------
    | Excluded Key Patterns
    |--------------------------------------------------------------------------
    |
    | Substring patterns in cache keys that should be excluded by the exporter.
    | This prevents cache traffic unrelated to the research scope (e.g., session
    | cache when SESSION_DRIVER=redis/memcached uses CacheBasedSessionHandler)
    | from being included in the measured metrics.
    |
    */
    'excluded_key_patterns' => [
        'laravel_session',
    ],
 
    /*
    |--------------------------------------------------------------------------
    | Allowed Observer IPs
    |--------------------------------------------------------------------------
    |
    | List of IP addresses allowed to access the /metrics/cache endpoint and
    | the cache reset endpoint/command.
    |
    */
    'allowed_ips' => [
        '127.0.0.1',
    ],
    
];