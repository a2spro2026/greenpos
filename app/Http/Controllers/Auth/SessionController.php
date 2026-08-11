<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BrandingService;
use App\Support\SessionManager;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (SessionManager::isLocked()) {
            return redirect()->route('session.lock');
        }

        $lastAccount = SessionManager::lastAccount($request);
        $loginBranding = null;
        $loginBrandingUrls = [];
        $rememberedCompany = null;
        $currentCompanyName = null;

        try {
            $userId = $lastAccount['id'] ?? Auth::id();
            if ($userId) {
                $user = User::query()->find($userId);
                $company = $user?->companies()->first();
                if ($company) {
                    $rememberedCompany = $company->name;
                    $svc = app(BrandingService::class);
                    $loginBranding = $svc->forCompany($company);
                    $loginBrandingUrls = [
                        'logo' => $svc->assetUrl($loginBranding['login_logo_path'] ?? null)
                            ?: $svc->assetUrl($loginBranding['logo_path'] ?? null),
                        'background' => $svc->assetUrl($loginBranding['login_background_path'] ?? null),
                    ];
                }
            }
            if (Auth::check()) {
                $currentCompanyName = Auth::user()?->companies()->first()?->name;
            }
        } catch (\Throwable) {
            //
        }

        if (is_array($lastAccount) && $rememberedCompany) {
            $lastAccount['company'] = $rememberedCompany;
        }

        return view('auth.login', [
            'lastAccount' => $lastAccount,
            'authenticated' => Auth::check(),
            'currentUser' => Auth::user(),
            'currentCompanyName' => $currentCompanyName,
            'sessionExpired' => $request->session()->pull('session_expired', false),
            'mode' => $request->query('mode', 'default'),
            'loginBranding' => $loginBranding,
            'loginBrandingUrls' => $loginBrandingUrls,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = User::findForLogin($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects. Vérifiez votre e-mail et votre mot de passe.',
            ]);
        }

        if (! $user->isActive() && $user->status !== 'invited') {
            throw ValidationException::withMessages([
                'email' => 'Ce compte est désactivé. Contactez un administrateur.',
            ]);
        }

        SessionManager::loginUser($user, $request, (bool) ($credentials['remember'] ?? false));

        if ($user->status === 'invited') {
            $user->forceFill(['status' => 'active'])->save();
        }

        if ($user->is_platform_admin) {
            $request->session()->forget([
                'workspace_company_id', 'workspace_store_id', 'workspace_store_filter',
                'url.intended',
            ]);

            return redirect()->route('admin.dashboard');
        }

        $company = $user->companies()->first();
        if ($company && in_array($company->status, ['inactive', 'archived'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Votre entreprise est actuellement suspendue. Veuillez contacter GreenPOS.',
            ]);
        }

        return redirect()->intended(route('home'));
    }

    public function continueAccount(Request $request): RedirectResponse
    {
        if (Auth::check() && ! SessionManager::isLocked()) {
            return redirect()->intended(route('home'));
        }

        $last = SessionManager::lastAccount($request);
        if (! $last) {
            return redirect()->route('login')->withErrors(['email' => 'Aucun compte mémorisé.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->find($last['id'] ?? 0)
            ?? User::query()->where('email', $last['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        if (! $user->isActive() && $user->status !== 'invited') {
            throw ValidationException::withMessages([
                'password' => 'Ce compte est désactivé.',
            ]);
        }

        SessionManager::loginUser($user, $request, (bool) ($data['remember'] ?? false));

        if ($user->is_platform_admin) {
            $request->session()->forget([
                'workspace_company_id', 'workspace_store_id', 'workspace_store_filter',
                'url.intended',
            ]);

            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('home'));
    }

    public function switchAccount(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            SessionManager::logout($request);
        }

        return redirect()->route('login', ['mode' => 'other'])
            ->withCookie(SessionManager::forgetLastAccount());
    }

    public function logout(Request $request): RedirectResponse
    {
        SessionManager::logout($request);

        return redirect()->route('login')
            ->with('status', 'Vous êtes déconnecté. À bientôt sur GreenPOS.');
    }

    public function showLock(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (! SessionManager::isLocked()) {
            SessionManager::lock();
        }

        $user = Auth::user();
        $company = Workspace::company();
        $store = Workspace::store();

        return view('auth.lock', [
            'user' => $user,
            'company' => $company,
            'store' => $store,
            'role' => $user->roleLabel($company),
            'lockedAt' => session(SessionManager::LOCK_AT_KEY),
        ]);
    }

    public function lock(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        SessionManager::lock();

        return redirect()->route('session.lock');
    }

    public function unlock(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();
        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Mot de passe incorrect.',
            ]);
        }

        SessionManager::unlock();
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function account(): View
    {
        $user = Auth::user();
        $company = Workspace::company();

        return view('account.index', [
            'user' => $user,
            'company' => $company,
            'store' => Workspace::store(),
            'role' => $user->roleLabel($company),
            'sessionLifetime' => config('session.lifetime'),
            'lockIdle' => config('greenpos.lock_idle_minutes'),
        ]);
    }

    public function preferences(): View
    {
        return view('account.preferences', [
            'user' => Auth::user(),
            'sessionLifetime' => config('session.lifetime'),
            'lockIdle' => config('greenpos.lock_idle_minutes'),
        ]);
    }
}
