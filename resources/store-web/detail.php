<?php

// Presentation only: controller-provided values, no lookups or model serialization.
// CURRENT TEMPORARY BACKEND CONTRACT / SW-01…SW-07 BACKEND SYNC OPEN.
abort_unless(app()->environment('local') && config('store-web.preview') === true &&
    ($storeWebAuthenticatedDataBridge ?? false) === true, 404);
$httpUrl = static function ($value) {
    $value = trim((string) $value);
    return filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
        ? $value : null;
};
$fallback = asset('store-web/img/no_image.png');
$imageUrl = static function ($value) use ($httpUrl, $fallback) {
    $url = $httpUrl($value);
    // Normalize the existing controller's legacy placeholder before making a request.
    return !$url || preg_match('~/no[-_]image\.png$~i', parse_url($url, PHP_URL_PATH)) ? $fallback : $url;
};
$detailName = (string) $material->full_name;
$detailImages = collect($images ?? [])->map(static function ($image) use ($imageUrl, $detailName) {
    return ['image' => $imageUrl($image['image'] ?? null), 'thumb' => $imageUrl($image['thumb'] ?? $image['image'] ?? null),
        'label' => (string) ($image['label'] ?? $detailName)];
})->values()->all();
if (!$detailImages) $detailImages = [['image' => $fallback, 'thumb' => $fallback, 'label' => $detailName]];
$detailBrand = $material->relationLoaded('brand') && $material->getRelation('brand') ? $material->getRelation('brand')->name : null;
$detailCode = $material->code ?: $material->codigo;
$detailAvailable = is_numeric($stockAvailable ?? null) ? (float) $stockAvailable > 0 : null;
$detailAvailability = $detailAvailable === null ? 'Disponibilidad por confirmar' : ($detailAvailable ? 'Disponible' : 'No disponible');
$detailShowPrice = in_array($showPricesCatalogEmpresa ?? false, [true, 1, '1', 's'], true);
$detailPhone = preg_replace('/\D/', '', (string) ($whatsappEmpresa ?? ''));
$detailUrl = route('shop.product.show', ['material' => $material->getRouteKey()]);
$detailClientContract = ['phone' => $detailPhone, 'name' => $detailName, 'url' => $detailUrl,
    'fallback' => $fallback, 'logoFallback' => asset('store-web/img/logo.png')];
$detailGroups = ['Talla' => $sizes ?? collect(), 'Color' => $colors ?? collect()];
// Already eager-loaded; reserved only. No presentation selector/price policy is inferred.
$detailPresentations = $material->relationLoaded('presentations') ? $material->getRelation('presentations') : collect();
$logo = trim((string) ($logotipoEmpresa ?? ''));
$detailBranding = ['logo' => $httpUrl($logo) ?: ($logo && basename($logo) === $logo ? asset('images/logo/' . rawurlencode($logo)) : asset('store-web/img/logo.png')),
    'label' => 'Catálogo del negocio', 'footer' => (string) ($descriptionFooterEmpresa ?? ''), 'socials' => []];
foreach (['instagram', 'tiktok', 'facebook', 'youtube'] as $social) {
    $detailBranding['socials'][$social] = $httpUrl($socialNetworksEmpresa[$social] ?? null);
}
