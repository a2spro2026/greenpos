<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

$failed = 0;
$passed = 0;

function ok(bool $c, string $m): void
{
    global $failed, $passed;
    if ($c) {
        $passed++;
        echo "  OK  {$m}\n";
    } else {
        $failed++;
        echo " FAIL {$m}\n";
    }
}

echo "=== GreenPOS site smoke ===\n";

$names = [
    'site.home', 'site.features', 'site.pricing', 'site.sectors',
    'site.about', 'site.contact', 'site.register', 'site.login',
    'register-company', 'login', 'home',
];

foreach ($names as $name) {
    ok(Route::has($name), "route {$name}");
}

ok(Route::getRoutes()->getByName('site.home')?->uri() === '/', 'site.home is /');
ok(Route::getRoutes()->getByName('home')?->uri() === 'app', 'ERP home is /app');

$uris = [
    '/', '/fonctionnalites', '/tarifs', '/secteurs', '/a-propos', '/contact',
    '/creer-mon-entreprise', '/connexion', '/register-company', '/login', '/app',
];

foreach ($uris as $uri) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    try {
        $route = Route::getRoutes()->match($request);
        ok((bool) $route, "match GET {$uri}");
    } catch (Throwable $e) {
        ok(false, "match GET {$uri} — ".$e->getMessage());
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
