<?php
namespace App\Http\Middleware;
use Closure;
class RetiredPromotionInput
{
    public function handle($request, Closure $next)
    {
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            \App\Services\Promotions\RetiredPromotionPolicy::payload($request->all());
        }
        return $next($request);
    }
}
