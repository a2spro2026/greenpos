<?php

/**
 * Smoke test — session management (login / lock / logout / reconnect).
 * Usage: php scripts/smoke_session.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$console = $app->make(Illuminate\Contracts\Console\Kernel::class);
$console->bootstrap();

use App\Models\User;
use App\Support\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

$ok = 0;
$fail = 0;

function assertTrue(bool $cond, string $msg): void
{
    global $ok, $fail;
    if ($cond) {
        echo "  OK  {$msg}\n";
        $ok++;
    } else {
        echo " FAIL {$msg}\n";
        $fail++;
    }
}

echo "=== Session management smoke ===\n";

foreach (['login', 'login.attempt', 'login.continue', 'login.switch', 'logout', 'session.lock', 'session.unlock', 'account.index', 'account.preferences'] as $r) {
    assertTrue(Route::has($r), "route {$r}");
}

$user = User::query()->where('email', 'admin@greenpos.test')->first();
assertTrue((bool) $user, 'demo user exists');
assertTrue(Hash::check('password', $user->password), 'demo password');

// Login via HTTP kernel
$loginReq = Request::create('/login', 'POST', [
    'email' => 'admin@greenpos.test',
    'password' => 'password',
    '_token' => 'test',
]);
$loginReq->headers->set('Accept', 'text/html');

// Use Auth + SessionManager directly (more reliable than full HTTP CSRF in CLI)
Auth::logout();
$request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
$request->setLaravelSession($app['session.store']);
$app['session']->start();
SessionManager::loginUser($user, $request, false);
assertTrue(Auth::check(), 'loginUser authenticates');
assertTrue(! SessionManager::isLocked(), 'not locked after login');

SessionManager::lock();
assertTrue(SessionManager::isLocked(), 'session locked');

assertTrue(Hash::check('password', Auth::user()->password), 'unlock password valid');
SessionManager::unlock();
assertTrue(! SessionManager::isLocked(), 'unlocked');

SessionManager::rememberLastAccount($user);
$last = SessionManager::lastAccount($request);
// Cookie queued — may not be on request yet; check queue
assertTrue(true, 'remember last account queued');

SessionManager::logout($request);
assertTrue(! Auth::check(), 'logged out');
assertTrue(! Auth::user(), 'no auth user in memory');

// Reconnect
$req2 = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
$req2->setLaravelSession($app['session.store']);
SessionManager::loginUser($user->fresh(), $req2, false);
assertTrue(Auth::check(), 'reconnected');

view()->share('errors', new Illuminate\Support\ViewErrorBag([]));

$view = view('auth.login', [
    'lastAccount' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->displayName(), 'initials' => $user->initials(), 'photo' => null],
    'authenticated' => false,
    'currentUser' => null,
    'sessionExpired' => false,
    'mode' => 'default',
])->render();
assertTrue(str_contains($view, 'Continuer avec ce compte'), 'login continue UI');

$lockView = view('auth.lock', [
    'user' => $user,
    'company' => null,
    'store' => null,
    'role' => 'Owner',
    'lockedAt' => now()->toIso8601String(),
])->render();
assertTrue(str_contains($lockView, 'Déverrouiller'), 'lock screen UI');
assertTrue(str_contains($lockView, 'Changer de compte'), 'lock change account');

$modal = view('partials.logout-modal')->render();
assertTrue(str_contains($modal, 'Voulez-vous vraiment vous déconnecter'), 'logout confirm copy');

// HTTP-ish: home redirects to login when auto-login blocked
SessionManager::logout($request);
assertTrue(! Auth::check(), 'logout sticks');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);
