<?php

namespace Mchardians\LaravelCacheExporter\Listeners {
    function apcu_enabled(): bool
    {
        if(\Mchardians\LaravelCacheExporter\Tests\Support\ApcuAvailabilityStub::$forceDisabled) {
            return false;
        }

        return \apcu_enabled();
    }
}

namespace Mchardians\LaravelCacheExporter\Http\Controllers {
    function apcu_enabled(): bool 
    {
        if(\Mchardians\LaravelCacheExporter\Tests\Support\ApcuAvailabilityStub::$forceDisabled) {
            return false;
        }

        return \apcu_enabled();
    }
}

namespace Mchardians\LaravelCacheExporter\Console\Commands {
    function apcu_enabled(): bool
    {
        if(\Mchardians\LaravelCacheExporter\Tests\Support\ApcuAvailabilityStub::$forceDisabled) {
            return false;
        }

        return \apcu_enabled();
    }
}