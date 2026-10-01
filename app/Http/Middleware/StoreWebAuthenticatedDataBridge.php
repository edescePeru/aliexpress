<?php

namespace App\Http\Middleware;

use Closure;

/** LOCAL AUTHENTICATED BRIDGE — NON PRODUCTION. SW-01 BACKEND SYNC OPEN. */
class StoreWebAuthenticatedDataBridge
{
    public function handle($request, Closure $next)
    {
        if (!$request->is('store-web', 'store-web/*')) {
            return $next($request);
        }
        abort_unless(app()->environment('local') && config('store-web.preview') === true, 404);
        $name = $request->route()->getName();
        abort_unless(in_array($name, ['store-web.catalog', 'shop.products.data',
            'shop.categories.data', 'shop.sizes.data', 'shop.colors.data', 'shop.product.show'], true), 404);
        abort_unless($request->isMethod('GET') || $request->isMethod('HEAD'), 404);

        $user = $request->user();
        if ((!$user && in_array($name, ['store-web.catalog', 'shop.product.show'], true)) ||
            ($name === 'shop.product.show' && $request->route('material') === 'frontend-fixture')) {
            // Preserve the anonymous local VISUAL preview; no business controller runs.
            return response()->view($name === 'shop.product.show' ? 'shop.detailCatalog' : 'shop.catalog', ['storeWebFixture' => true])
                ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow')
                ->header('X-Store-Web-Preview', 'visual-qa-frontend-fixture');
        }
        abort_unless($user && !$user->isPlatformAdmin() && $user->enable != 0, 404);

        // Existing authenticated session ONLY. Never initialize/default a missing context.
        $tenantId = (int) $request->session()->get('multitenancy.tenant_id');
        $companyId = (int) $request->session()->get('multitenancy.company_id');
        abort_unless($tenantId > 0 && $companyId > 0 && $tenantId === (int) $user->tenant_id, 404);
        abort_unless($user->tenant && $user->tenant->is_active, 404);
        // Same membership/active predicates used by InitializeTenantContext; no business query changes.
        abort_unless($user->companies()->where('companies.id', $companyId)
            ->where('companies.tenant_id', $tenantId)->where('companies.is_active', true)
            ->wherePivot('is_active', true)->exists(), 404);

        $views = app('view');
        $previous = $views->getShared();
        $views->share(['storeWebAuthenticatedDataBridge' => true, 'storeWebFixture' => false]);
        try {
            $response = $next($request);
        } finally {
            $views->share(['storeWebAuthenticatedDataBridge' => $previous['storeWebAuthenticatedDataBridge'] ?? false,
                'storeWebFixture' => $previous['storeWebFixture'] ?? false]);
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('X-Store-Web-Preview', 'local-authenticated-data-bridge-non-production');
        return $response;
    }
}
