<?php

namespace Mchardians\LaravelCacheExporter\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToObserverIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('cache-exporter.allowed_ips', []);

        if(!in_array($request->ip(), $allowedIps, true)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}