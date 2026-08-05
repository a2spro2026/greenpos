<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        // During impersonation, keep tenant session — never force platform logout
        if ($request->session()->has('admin_impersonator_id')) {
            return redirect()->route('home')
                ->with('info', 'Mode entreprise actif. Quittez l’impersonation pour revenir au Super Admin.');
        }

        if (! $user->is_platform_admin) {
            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Accès réservé au propriétaire de la plateforme GreenPOS.']);
        }

        view()->share('platformAdminUser', $user);

        $pendingRegistrations = 0;
        $unreadPlatformNotifications = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('company_registration_requests')) {
                $pendingRegistrations = \App\Models\CompanyRegistrationRequest::query()
                    ->where('status', \App\Models\CompanyRegistrationRequest::STATUS_PENDING)
                    ->count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('platform_notifications')) {
                $unreadPlatformNotifications = \App\Models\PlatformNotification::query()
                    ->unread()
                    ->where(function ($q) use ($user) {
                        $q->whereNull('user_id')->orWhere('user_id', $user->id);
                    })
                    ->count();
            }
        } catch (\Throwable) {
            //
        }
        view()->share('platformPendingRegistrations', $pendingRegistrations);
        view()->share('platformUnreadNotifications', $unreadPlatformNotifications);

        return $next($request);
    }
}
