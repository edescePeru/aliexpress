<?php

// Local, isolated visual QA only. Never put this router in public/ or deployment routing.
// Run with PHP 7.3.33 -S 127.0.0.1:8097 tests/StoreWebPreview/router.php.
// Does not dispatch application controllers, model bindings or business endpoints.
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Store-Web-Preview: frontend-fixture-no-business-data');

// Explicit allowlists; no fallback to the development webroot or Laravel HTTP kernel.
$assetRoots = ['/store-web/' => $root . '/public/store-web/'];
// Optional external reference for historical pixel comparisons; never an app dependency.
$referenceRoot = getenv('STORE_WEB_QA_REFERENCE_ROOT');
if (getenv('STORE_WEB_QA_REFERENCE') === '1' && $referenceRoot && is_dir($referenceRoot)) {
    $assetRoots['/reference/'] = realpath($referenceRoot) . DIRECTORY_SEPARATOR;
}
$types = ['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png',
    'jpg' => 'image/jpeg', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'html' => 'text/html'];
foreach ($assetRoots as $prefix => $directory) {
    if (strpos($uri, $prefix) !== 0) {
        continue;
    }
    $candidate = realpath($directory . substr($uri, strlen($prefix)));
    $base = realpath($directory);
    $extension = $candidate ? strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) : '';
    if ($candidate && $base && strpos($candidate, $base . DIRECTORY_SEPARATOR) === 0 && is_file($candidate) && isset($types[$extension])) {
        header('Content-Type: ' . $types[$extension]);
        readfile($candidate);
        exit;
    }
}

$views = ['/store-web/inicio' => 'shop.home', '/store-web/catalogo' => 'shop.catalog',
    '/store-web/product/frontend-fixture' => 'shop.detailCatalog'];
if (!isset($views[$uri]) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(404);
    exit('Not a fixture preview endpoint.');
}

require $root . '/vendor/autoload.php';
// Use the project's installed Laravel Blade + URL generator, but no application
// providers/.env/HTTP kernel. AppServiceProvider resolves Schema during boot;
// even that is intentionally excluded. No DB/session/auth service is registered.
$app = new Illuminate\Foundation\Application($root);
$app->instance('env', 'local');
$cache = sys_get_temp_dir() . '/venti-store-web-qa-blade';
if (!is_dir($cache)) {
    mkdir($cache, 0700, true);
}
$app->instance('config', new Illuminate\Config\Repository([
    'store-web' => ['preview' => true],
    'view' => ['paths' => [$root . '/resources/views'], 'compiled' => $cache],
    'app' => ['url' => 'http://' . $_SERVER['HTTP_HOST'], 'asset_url' => null],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$app->register(Illuminate\Events\EventServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(Illuminate\View\ViewServiceProvider::class);
$request = Illuminate\Http\Request::create('http://' . $_SERVER['HTTP_HOST'] . $uri);
$app->instance('request', $request);
// URL metadata mirrors the existing names; these routes are never dispatched.
$routes = new Illuminate\Routing\RouteCollection();
foreach (['store-web.home' => 'store-web/inicio', 'store-web.catalog' => 'store-web/catalogo',
    'shop.product.show' => 'store-web/product/{material}',
    'shop.products.data' => 'store-web/products/data/{pageNumber}',
    'shop.categories.data' => 'store-web/categories/data',
    'shop.sizes.data' => 'store-web/sizes/data',
    'shop.colors.data' => 'store-web/colors/data'] as $name => $path) {
    $route = new Illuminate\Routing\Route('GET', $path, function () {});
    $route->name($name);
    $routes->add($route);
}
$app->instance('url', new Illuminate\Routing\UrlGenerator($routes, $request));
$app->boot();
header('Content-Type: text/html; charset=UTF-8');
echo $app['view']->make($views[$uri], ['storeWebFixture' => true])->render();
