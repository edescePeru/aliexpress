<?php
namespace App\Http\Middleware;

use App\Services\SelfProfileAccess;
use Closure;

class EnsureOwnProfile
{
    public function handle($request, Closure $next)
    {
        SelfProfileAccess::assertSelf($request->user(), $request->route('user'));
        return $next($request);
    }
}
