<?php
namespace App\Http\Middleware;

use App\Services\Sales\PosCatalogReadService;
use App\Services\Materials\MaterialInventoryScope;
use Closure;

class PosCatalogGuard
{
    public function handle($request, Closure $next, string $version = 'either', string $mode = 'endpoint')
    {
        app(PosCatalogReadService::class)->authorize($version);
        if ($mode !== 'page') { app(MaterialInventoryScope::class)->snapshot($request); }
        return $next($request);
    }
}
