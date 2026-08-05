<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Billing\PaymentGatewayManager;
use App\Http\Controllers\Controller;
use App\Models\SaasPaymentGateway;
use App\Services\SaasBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private SaasBillingService $billing,
        private PaymentGatewayManager $gateways,
    ) {
    }

    public function dashboard(): View
    {
        $this->gateways->ensureDefaults();
        $stats = $this->billing->billingDashboard();

        return view('superadmin.billing.dashboard', compact('stats'));
    }

    public function gateways(): View
    {
        $this->gateways->ensureDefaults();
        $board = $this->gateways->statusBoard();
        $rows = SaasPaymentGateway::query()->orderBy('code')->get()->keyBy('code');

        return view('superadmin.billing.gateways', compact('board', 'rows'));
    }

    public function updateGateway(Request $request, SaasPaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'mode' => ['required', 'in:test,live'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'merchant_id' => ['nullable', 'string', 'max:255'],
            'store_key' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'status_message' => ['nullable', 'string', 'max:500'],
        ]);

        $creds = $gateway->credentials ?? [];
        foreach (['secret_key', 'public_key', 'client_id', 'client_secret', 'merchant_id', 'store_key'] as $key) {
            if (! empty($data[$key])) {
                $creds[$key] = $data[$key];
            }
        }

        $gateway->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'is_sandbox' => ($data['mode'] ?? 'test') === 'test',
            'mode' => $data['mode'],
            'credentials' => $creds,
            'webhook_secret' => $data['webhook_secret'] ?? $gateway->webhook_secret,
            'status' => $request->boolean('is_enabled') ? 'connected' : 'disabled',
            'status_message' => $data['status_message'] ?? $gateway->status_message,
            'last_tested_at' => now(),
        ]);

        return back()->with('success', 'Passerelle '.$gateway->name.' mise à jour.');
    }

    public function runBillingJob(): RedirectResponse
    {
        $reminders = $this->billing->sendRenewalReminders(14);
        $trials = $this->billing->processExpiredTrials(true);
        $renewals = $this->billing->processAutoRenewals();

        return back()->with('success', sprintf(
            'Job billing : %d rappels · %d essais convertis · %d expirés · %d renouvelés · %d échecs',
            $reminders,
            $trials['converted'],
            $trials['expired'],
            $renewals['renewed'],
            $renewals['failed']
        ));
    }
}
