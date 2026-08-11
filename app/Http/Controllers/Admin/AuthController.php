<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->is_platform_admin && ! session('admin_impersonator_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::findForLogin($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants Super Admin invalides.',
            ]);
        }

        if (! $user->is_platform_admin) {
            throw ValidationException::withMessages([
                'email' => 'Ce compte n’est pas un Super Admin plateforme.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Compte Super Admin inactif.',
            ]);
        }

        // Clear any client workspace / impersonation before admin session
        $request->session()->forget([
            'workspace_company_id', 'workspace_store_id', 'workspace_store_filter',
            'admin_impersonator_id', 'admin_impersonating_company_id', 'admin_impersonating_company_name',
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        SessionManager::clearAutoLoginBlock();

        // Never follow a stale ERP "intended" URL — console only
        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Déconnexion Super Admin effectuée.');
    }
}
