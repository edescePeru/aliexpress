<?php

namespace App\Http\Middleware;

use Closure;

class StoreWebLocalPreview
{
    public function handle($request, Closure $next)
    {
        if (!$request->is('store-web', 'store-web/*')) {
            return $next($request);
        }

        // Fail closed outside explicit local QA BEFORE sessions/bindings/controllers.
        abort_unless(app()->environment('local') && config('store-web.preview') === true, 404);

        $dataRoutes = ['store-web.catalog', 'shop.products.data', 'shop.categories.data',
            'shop.sizes.data', 'shop.colors.data', 'shop.product.show'];
        if (in_array($request->route()->getName(), $dataRoutes, true)) {
            abort_unless($request->isMethod('GET') || $request->isMethod('HEAD'), 404);
            // Authentication/context validation occurs AFTER StartSession and BEFORE bindings.
            return $next($request);
        }

        $views = [
            'store-web.home' => 'shop.home',
        ];
        $name = $request->route()->getName();
        abort_unless($request->isMethod('GET') || $request->isMethod('HEAD'), 404);
        abort_unless(isset($views[$name]), 404);

        // Home stays a visual fixture; product binding is gated by the next bridge.
        return response()->view($views[$name], ['storeWebFixture' => true])
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('X-Store-Web-Preview', 'visual-qa-frontend-fixture');
    }
}
