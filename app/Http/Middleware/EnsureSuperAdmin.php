<?php

namespace App\Http\Middleware;

use App\Support\SessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Local demo: elevate platform admin if flag missing but email matches
        if (! $user && SessionManager::autoLoginAllowed($request)) {
            $demo = \App\Models\User::query()
                ->where('email', 'admin@greenpos.test')
                ->first();
            if ($demo) {
                if (! $demo->is_platform_admin) {
                    $demo->forceFill(['is_platform_admin' => true])->save();
                }
                Auth::login($demo);
                $user = $demo;
            }
        }

        if (! $user || ! $user->is_platform_admin) {
            abort(403, 'Accès Super Administration réservé.');
        }

        view()->share('superAdminUser', $user);

        return $next($request);
    }
}
