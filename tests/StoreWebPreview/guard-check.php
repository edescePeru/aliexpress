<?php

// CLI guard check. No application bootstrap, DB service, routes or business data.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$app = new Illuminate\Foundation\Application($root);
$cache = sys_get_temp_dir() . '/venti-store-web-qa-blade';
if (!is_dir($cache)) {
    mkdir($cache, 0700, true);
}
$app->instance('config', new Illuminate\Config\Repository([
    'store-web' => ['preview' => true],
    'view' => ['paths' => [$root . '/resources/views'], 'compiled' => $cache],
]));
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$app->register(Illuminate\Events\EventServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(Illuminate\View\ViewServiceProvider::class);
$app->boot();
$count = 0;
foreach (['shop.home', 'shop.catalog', 'shop.detailCatalog'] as $view) {
    foreach ([['production', true], ['testing', false], ['local', 'true'], ['local', null]] as $case) {
        $app->instance('env', $case[0]);
        try {
            // Layout checks the guard before assets, navigation or fixture content.
            $app['view']->make('layouts.storeWeb', ['storeWebFixture' => $case[1], 'storeWebPage' => $view])->render();
            throw new RuntimeException('Guard unexpectedly allowed ' . $case[0]);
        } catch (Throwable $exception) {
            $cause = $exception;
            while ($cause->getPrevious()) {
                $cause = $cause->getPrevious();
            }
            if (!($cause instanceof Symfony\Component\HttpKernel\Exception\HttpException) || $cause->getStatusCode() !== 404) {
                throw $exception;
            }
            $count++;
        }
    }
}
echo 'PASS: ' . $count . ' fixture guard cases denied (production, no opt-in, string flag, null).' . PHP_EOL;
