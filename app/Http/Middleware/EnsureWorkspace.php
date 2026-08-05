<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\OnboardingService;
use App\Support\SessionManager;
use App\Support\Workspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() && SessionManager::autoLoginAllowed($request)) {
            // Prefer demo ERP user if present; never auto-login platform Super Admin into ERP
            $demo = User::query()->where('email', 'admin@greenpos.test')->where('is_platform_admin', false)->first();
            if ($demo) {
                Auth::login($demo);
            }
        }

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

        // ─── Strict split: Platform Super Admin never enters ERP unless impersonating ───
        $user = Auth::user();
        $impersonating = $request->session()->has('admin_impersonator_id')
            || $request->session()->has('admin_impersonating_company_id');

        if ($user?->is_platform_admin && ! $impersonating) {
            $request->session()->forget([
                'workspace_company_id',
                'workspace_store_id',
                'workspace_store_filter',
            ]);

            return redirect()->route('admin.dashboard');
        }

        if (SessionManager::isLocked()
            && ! $request->routeIs('session.lock', 'session.unlock', 'session.lock.store', 'logout')) {
            return redirect()->route('session.lock');
        }

        // SaaS onboarding: account created but workspace not provisioned yet
        if (! $request->routeIs('onboarding.*', 'logout', 'login', 'login.*')) {
            try {
                $onboarding = app(OnboardingService::class);
                if ($onboarding->needsPlan(Auth::user())) {
                    return redirect()->route('onboarding.plan');
                }
            } catch (\Throwable) {
                //
            }
        }

        if (Auth::check() && ! Workspace::company()) {
            $company = Auth::user()->companies()->first();
            if ($company) {
                Workspace::set($company, $company->stores()->first());
            }
        }

        // Block ERP access for suspended / inactive companies
        $workspaceCompany = Workspace::company();
        if ($workspaceCompany
            && ! $request->routeIs('logout', 'company.suspended', 'session.lock', 'session.unlock', 'session.lock.store')
            && ! $impersonating
        ) {
            $isSuspended = $workspaceCompany->status === 'inactive'
                || $workspaceCompany->status === 'archived';

            if (! $isSuspended) {
                try {
                    $tenant = \App\Models\SaasTenant::query()
                        ->where('company_id', $workspaceCompany->id)
                        ->first();
                    if ($tenant && method_exists($tenant, 'isSuspended') && $tenant->isSuspended()) {
                        $isSuspended = true;
                    } elseif ($tenant && $tenant->status === 'suspended') {
                        $isSuspended = true;
                    }
                } catch (\Throwable) {
                    //
                }
            }

            if ($isSuspended) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Votre entreprise est actuellement suspendue. Veuillez contacter GreenPOS.',
                        'code' => 'company_suspended',
                    ], 403);
                }

                return response()->view('company-registration.suspended', [
                    'companyName' => $workspaceCompany->name,
                ], 403);
            }
        }

        if (! Workspace::company()) {
            $user = Auth::user();

            // Auto-heal empty / partially seeded databases (tenant accounts only)
            if (! $user?->is_platform_admin) {
                try {
                    app(\App\Services\PlatformBootstrapService::class)->ensureIfEmpty();
                    $company = $user?->companies()->first();
                    if ($company) {
                        Workspace::set($company, $company->stores()->first());
                    }
                } catch (\Throwable) {
                    //
                }
            }
        }

        if (! Workspace::company()) {
            try {
                if (app(OnboardingService::class)->needsPlan(Auth::user())) {
                    return redirect()->route('onboarding.plan');
                }
            } catch (\Throwable) {
                //
            }

            if (Auth::user()?->is_platform_admin) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()
                ->route('login')
                ->with('error', 'Aucun espace entreprise trouvé pour ce compte. Contactez le support GreenPOS.');
        }

        if (! $request->routeIs('onboarding.*', 'logout')) {
            try {
                if (app(OnboardingService::class)->needsWizard(Auth::user())) {
                    return redirect()->route('onboarding.wizard');
                }
            } catch (\Throwable) {
                //
            }
        }

        // Isolation boutique: injecte store_id dans les filtres GET si absent
        if ($request->isMethod('GET') && ! $request->filled('store_id')) {
            $filterId = Workspace::storeFilterId();
            if ($filterId) {
                $request->merge(['store_id' => $filterId]);
            }
        }

        view()->share('workspaceCompany', Workspace::company());
        view()->share('workspaceStore', Workspace::store());
        view()->share('workspaceRole', Workspace::role());
        view()->share('workspaceAccessibleStores', Workspace::accessibleStores());
        view()->share('workspaceStoreFilterAll', session('workspace_store_filter') === 'all' && Workspace::canAccessAllStores());
        view()->share('workspaceAccessibleCompanies', Workspace::accessibleCompanies());
        view()->share('workspaceSwitchableCompanies', Workspace::switchableCompanies());

        try {
            if (Auth::check() && Workspace::company() && \Illuminate\Support\Facades\Schema::hasTable('app_notifications')) {
                $notifService = app(\App\Services\NotificationService::class);
                view()->share('workspaceUnreadNotifications', $notifService->unreadCount());
                view()->share('workspaceLatestNotifications', $notifService->latest(6));
            } else {
                view()->share('workspaceUnreadNotifications', 0);
                view()->share('workspaceLatestNotifications', collect());
            }
        } catch (\Throwable) {
            view()->share('workspaceUnreadNotifications', 0);
            view()->share('workspaceLatestNotifications', collect());
        }

        try {
            if (Workspace::company()) {
                app(\App\Services\ModuleManagerService::class)->ensureSynced(Workspace::company());
            }
        } catch (\Throwable) {
            //
        }

        try {
            view()->share('onboardingChecklist', app(OnboardingService::class)->dashboardChecklist());
        } catch (\Throwable) {
            view()->share('onboardingChecklist', null);
        }

        try {
            if (Workspace::company() && \Illuminate\Support\Facades\Schema::hasTable('company_settings')) {
                $brandingService = app(\App\Services\BrandingService::class);
                $branding = $brandingService->forCompany(Workspace::company());
                view()->share('workspaceBranding', $branding);
                view()->share('workspaceBrandingCss', $brandingService->cssVariables($branding));
                view()->share('workspaceBrandingFavicon', $brandingService->assetUrl($branding['favicon_path'] ?? null));
                view()->share('workspaceBrandingLogo', $brandingService->assetUrl($branding['logo_path'] ?? null)
                    ?: $brandingService->assetUrl($branding['logo_compact_path'] ?? null));
            } else {
                view()->share('workspaceBranding', null);
                view()->share('workspaceBrandingCss', []);
                view()->share('workspaceBrandingFavicon', null);
                view()->share('workspaceBrandingLogo', null);
            }
        } catch (\Throwable) {
            view()->share('workspaceBranding', null);
            view()->share('workspaceBrandingCss', []);
            view()->share('workspaceBrandingFavicon', null);
            view()->share('workspaceBrandingLogo', null);
        }

        return $next($request);
    }
}
