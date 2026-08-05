<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(private PlatformAdminService $platform)
    {
    }

    public function leave(Request $request): RedirectResponse
    {
        $admin = $this->platform->stopImpersonation($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('admin.dashboard')->with('success', 'Impersonation terminée. Retour Super Admin.');
    }
}
