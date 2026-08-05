<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasDomain;
use App\Models\SaasTenant;
use App\Services\SaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function index(): View
    {
        $domains = SaasDomain::query()->with('tenant')->latest()->paginate(25);
        $tenants = SaasTenant::query()->orderBy('name')->get(['id', 'name']);

        return view('superadmin.domains.index', compact('domains', 'tenants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'saas_tenant_id' => ['required', 'exists:saas_tenants,id'],
            'domain' => ['required', 'string', 'max:255', 'unique:saas_domains,domain'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $tenant = SaasTenant::query()->findOrFail($data['saas_tenant_id']);
        $this->saas->addDomain($tenant, $data['domain'], $request->boolean('is_primary'));

        return back()->with('success', 'Domaine ajouté.');
    }

    public function verify(SaasDomain $domain): RedirectResponse
    {
        $domain->update([
            'status' => 'active',
            'ssl_enabled' => true,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Domaine vérifié.');
    }

    public function destroy(SaasDomain $domain): RedirectResponse
    {
        $domain->delete();

        return back()->with('success', 'Domaine supprimé.');
    }
}
