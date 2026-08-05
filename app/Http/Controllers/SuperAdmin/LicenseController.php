<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasLicense;
use App\Services\SaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString() ?: 'active';

        $base = SaasLicense::query()->with(['tenant', 'subscription.plan']);

        $counts = [
            'active' => (clone $base)->where('status', 'active')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
            'expired' => (clone $base)->where(fn ($q) => $q->where('status', 'expired')->orWhere(fn ($q2) => $q2->where('status', 'active')->where('expires_at', '<=', now())))->count(),
            'renewals' => (clone $base)->whereHas('subscription', fn ($q) => $q->where('renewal_count', '>', 0))->count(),
            'revoked' => (clone $base)->where('status', 'revoked')->count(),
            'all' => (clone $base)->count(),
        ];

        $licenses = match ($tab) {
            'expired' => (clone $base)->where(function ($q) {
                $q->where('status', 'expired')
                    ->orWhere(fn ($q2) => $q2->where('status', 'active')->where('expires_at', '<=', now()));
            })->latest()->paginate(25),
            'renewals' => (clone $base)->whereHas('subscription', fn ($q) => $q->where('renewal_count', '>', 0))
                ->latest('issued_at')->paginate(25),
            'revoked' => (clone $base)->where('status', 'revoked')->latest('revoked_at')->paginate(25),
            'history' => (clone $base)->latest()->paginate(25),
            default => (clone $base)->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest()
                ->paginate(25),
        };

        $licenses->appends(['tab' => $tab]);

        return view('superadmin.licenses.index', compact('licenses', 'tab', 'counts'));
    }

    public function revoke(SaasLicense $license): RedirectResponse
    {
        $license->update(['status' => 'revoked', 'revoked_at' => now()]);
        $this->saas->logAudit('billing', 'warning', 'Licence révoquée', $license->license_key, $license->tenant);

        return back()->with('success', 'Licence révoquée.');
    }

    public function renew(SaasLicense $license): RedirectResponse
    {
        $sub = $license->subscription;
        $ends = $sub?->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth();

        $license->update([
            'status' => 'active',
            'expires_at' => $ends,
            'revoked_at' => null,
            'issued_at' => now(),
        ]);

        if ($sub) {
            $sub->update([
                'renewal_count' => ((int) $sub->renewal_count) + 1,
                'ends_at' => $ends,
                'renews_at' => $ends,
                'status' => 'active',
            ]);
        }

        $this->saas->logAudit('billing', 'info', 'Licence renouvelée', $license->license_key, $license->tenant);

        return back()->with('success', 'Licence renouvelée jusqu’au '.$ends->format('d/m/Y').'.');
    }
}
