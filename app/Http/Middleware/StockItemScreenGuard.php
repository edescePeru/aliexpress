<?php
namespace App\Http\Middleware;

use App\Services\Materials\MaterialInventoryScope;
use Closure;

class StockItemScreenGuard
{
    public function handle($request, Closure $next, string $mode = 'endpoint')
    {
        $scope = app(MaterialInventoryScope::class);
        $scope->context();
        if ($mode !== 'page' || (!$request->isMethod('GET') && !$request->isMethod('HEAD'))) {
            $scope->snapshot($request);
        }
        return $next($request);
    }
}
