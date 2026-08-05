<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use App\Models\SaasTenant;
use App\Services\SaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function index(Request $request): View
    {
        $payments = SaasPayment::query()
            ->with('tenant')
            ->when($request->filled('provider'), fn ($q) => $q->where('provider', $request->string('provider')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.payments.index', [
            'payments' => $payments,
            'providers' => SaasPayment::PROVIDERS,
            'statuses' => SaasPayment::STATUSES,
            'filters' => $request->only(['provider', 'status']),
            'invoices' => SaasInvoice::query()->with('tenant')->latest()->limit(10)->get(),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.payments.create', [
            'tenants' => SaasTenant::query()->orderBy('name')->get(),
            'providers' => SaasPayment::PROVIDERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'saas_tenant_id' => ['required', 'exists:saas_tenants,id'],
            'provider' => ['required', 'in:stripe,paypal,cmi,manual'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,failed'],
        ]);

        $this->saas->recordPayment($data);

        return redirect()->route('superadmin.payments.index')->with('success', 'Paiement enregistré.');
    }
}
