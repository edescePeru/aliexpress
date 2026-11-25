<?php
namespace App\Http\Middleware;

use App\Support\OperationalModules;
use Closure;

class OperationalModuleGate
{
    public function handle($request, Closure $next, string $module)
    {
        $definition = OperationalModules::definition($module);
        if ($definition['enabled']) { return $next($request); }
        $data = ['message' => $definition['message'], 'module' => $module];
        if ($request->expectsJson()) { return response()->json($data, $definition['status']); }
        return response()->view('operationalModules.unavailable', $data, $definition['status']);
    }
}
