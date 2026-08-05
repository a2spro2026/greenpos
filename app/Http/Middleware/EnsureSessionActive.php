<?php

namespace App\Http\Middleware;

use App\Support\SessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the user is authenticated and the session is not locked.
 * Applied after EnsureWorkspace (or together) on protected routes.
 */
class EnsureSessionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session expirée. Veuillez vous reconnecter.',
                    'code' => 'session_expired',
                ], 401);
            }

            $request->session()->put('session_expired', true);
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login');
        }

        if (SessionManager::isLocked()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Session verrouillée.',
                    'code' => 'session_locked',
                ], 423);
            }

            if (! $request->routeIs('session.lock', 'session.unlock', 'session.lock.store', 'logout', 'login', 'login.*')) {
                return redirect()->route('session.lock');
            }
        }

        return $next($request);
    }
}
