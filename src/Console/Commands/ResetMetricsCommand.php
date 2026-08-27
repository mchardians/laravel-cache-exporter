<?php

namespace Mchardians\LaravelCacheExporter\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class ResetMetricsCommand extends Command
{
    protected $signature = 'cache-exporter:reset';

    protected $description = 'Reset cache exporter metrics by advancing the generation counter';

    public function handle(): int
    {
        if(!function_exists('apcu_enabled') || !apcu_enabled()) {
            $this->error('APCU is not enabled. Nothing to reset.');
            
            return self::FAILURE;
        }

        $generationKey = 'cache_exporter:generation';

        try {
            $newGeneration = apcu_inc($generationKey, 1, $success);
    
            if(!$success) {
                apcu_add($generationKey, 1);
                $newGeneration = 1;
            }
        } catch(Throwable $e) {
            $this->error("Failed to reset metrics: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Cache exporter metrics reset. New generation: {$newGeneration}");

        return self::SUCCESS;
    }
}