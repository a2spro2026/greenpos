<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private PlatformAdminService $platform)
    {
    }

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => $this->platform->platformSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:64'],
            'default_trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_country' => ['nullable', 'string', 'max:120'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:1000'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'allow_self_signup' => ['sometimes', 'boolean'],
        ]);
        $data['maintenance_mode'] = $request->boolean('maintenance_mode');
        $data['allow_self_signup'] = $request->boolean('allow_self_signup');

        $this->platform->savePlatformSettings($data);

        return back()->with('success', 'Paramètres plateforme enregistrés.');
    }
}
