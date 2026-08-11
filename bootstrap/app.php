<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/site.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/superadmin.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/ai.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/crm.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/onboarding.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/registration.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'workspace' => \App\Http\Middleware\EnsureWorkspace::class,
            'audit' => \App\Http\Middleware\LogAuditActivity::class,
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'session.active' => \App\Http\Middleware\EnsureSessionActive::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('build/*', 'up')) {
                return null;
            }

            $previous = $e->getPrevious();
            $isMissingModel = $previous instanceof \Illuminate\Database\Eloquent\ModelNotFoundException;

            if ($request->is('admin/*') && ($isMissingModel || $request->is('admin/registrations/*', 'admin/companies/*'))) {
                $target = $request->is('admin/registrations*') ? 'admin.registrations.index' : (
                    $request->is('admin/companies*') ? 'admin.companies.index' : 'admin.dashboard'
                );

                return redirect()->route($target)
                    ->with('warning', 'Cet élément n’existe plus (données de test réinitialisées).');
            }

            if ($request->is('products*')) {
                return redirect()->route('products.index')
                    ->with('warning', 'Ce produit n’existe plus.');
            }

            if ($isMissingModel && $request->user() && ! $request->user()->is_platform_admin) {
                return redirect()->route('home')
                    ->with('warning', 'Cet élément n’existe plus.');
            }

            return null;
        });

        $exceptions->respond(function ($response, $exception, $request) {
            if ($response->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session expirée (jeton CSRF invalide). Rechargez la page.',
                        'code' => 'csrf_expired',
                    ], 419);
                }

                return redirect()->route('login')
                    ->with('session_expired', true)
                    ->with('status', 'Votre session a expiré. Veuillez vous reconnecter.');
            }

            return $response;
        });
    })->create();
