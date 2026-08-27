<?php

namespace Mchardians\LaravelCacheExporter\Listeners;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Cache\Events\WritingKey;
use Illuminate\Events\Dispatcher;
use Throwable;

class CacheEventSubscriber
{
    private array $startTimes = [
        'read' => [],
        'write' => []
    ];

    private string $generationKey = 'cache_exporter:generation';
    private int $generation = 0;
    private bool $apcuAvailable = false;

    public function __construct()
    {
        $this->apcuAvailable = function_exists('apcu_enabled') && apcu_enabled();
        
        if ($this->apcuAvailable) {
            $this->generation = $this->safeApcuFetch($this->generationKey, 0);
        }
    }

    public function handleRetrieving(RetrievingKey $event): void
    {
        if (!$this->apcuAvailable || $this->shouldSkip($event->key, $event->storeName)){
            return;
        }

        $this->startTimes['read'][$event->key] = hrtime(true);
    }

    public function handleHit(CacheHit $event): void
    {
        $this->recordMetric('read', 'hits', $event->key, $event->storeName);
    }

    public function handleMissed(CacheMissed $event): void
    {
        $this->recordMetric('read', 'misses', $event->key, $event->storeName);
    }

    public function handleWriting(WritingKey $event): void
    {
        if (!$this->apcuAvailable || $this->shouldSkip($event->key, $event->storeName)) {
            return;
        };

        $this->startTimes['write'][$event->key] = hrtime(true);
    }

    public function handleWritten(KeyWritten $event): void
    {
        $this->recordMetric('write', 'writes', $event->key, $event->storeName);
    }

    private function recordMetric(string $operation, string $metricType, string $key, ?string $storeName): void
    {
        if (!$this->apcuAvailable || $this->shouldSkip($key, $storeName)) {
            return;
        }
        
        if (!isset($this->startTimes[$operation][$key])) {
            return;
        }

        $durationMicro = (int) ((hrtime(true) - $this->startTimes[$operation][$key]) / 1000);
        
        unset($this->startTimes[$operation][$key]);

        $engine = $storeName ?? 'unknown';
        $prefix = "cache_exporter:gen{$this->generation}:{$engine}";

        $this->safeApcuInc("{$prefix}:{$metricType}_total", 1);

        if($operation === 'read') {
            $this->safeApcuInc("{$prefix}:requests_total", 1);
        }
        
        $this->safeApcuInc("{$prefix}:duration_count", 1);
        $this->safeApcuInc("{$prefix}:duration_sum", $durationMicro);
        $this->safeApcuInc("{$prefix}:duration_sq_sum", $durationMicro * $durationMicro);
    }

    private function safeApcuInc(string $key, int $value): void
    {
        try {
            apcu_inc($key, $value, $success, 0);
        } catch(Throwable) {
            try {
                apcu_inc('cache_exporter:exporter_errors_total', 1);
            }catch(Throwable) {
                // 
            }
        }
    }

    private function safeApcuFetch(string $key, int $default): int 
    {
        try {
            $value = apcu_fetch($key);

            return $value === false ? $default : (int) $value;
        } catch(Throwable) {
            return $default;
        }
    }

    private function shouldSkip(string $key, ?string $storeName): bool
    {
        return $this->shouldIgnoreKey($key) || !$this->isStoreAllowed($storeName);
    }

    private function shouldIgnoreKey(string $key): bool
    {
        $excluded = config('cache-exporter.excluded_key_patterns', ['laravel_session']);
        
        foreach ($excluded as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    private function isStoreAllowed(?string $storeName): bool 
    {
        $onlyStore = config('cache-exporter.only_store', []);

        if(empty($onlyStore)) {
            return true;
        }

        $engine = $storeName ?? 'unknown';

        return in_array($engine, $onlyStore, true);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(RetrievingKey::class, [$this, 'handleRetrieving']);
        $events->listen(CacheHit::class, [$this, 'handleHit']);
        $events->listen(CacheMissed::class, [$this, 'handleMissed']);
        
        $events->listen(WritingKey::class, [$this, 'handleWriting']);
        $events->listen(KeyWritten::class, [$this, 'handleWritten']);
    }
}