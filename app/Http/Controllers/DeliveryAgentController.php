<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAgent;
use App\Models\Store;
use App\Services\DeliveryAgentService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryAgentController extends Controller
{
    public function __construct(private DeliveryAgentService $agents)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('delivery.view');
        $company = Workspace::company();

        $agents = DeliveryAgent::query()
            ->forCompany($company->id)
            ->with('store')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('platform_name', 'like', $term);
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('active'), function ($q) use ($request) {
                if ($request->string('active') === '1') {
                    $q->where('is_active', true);
                } elseif ($request->string('active') === '0') {
                    $q->where('is_active', false);
                }
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('delivery.index', [
            'agents' => $agents,
            'types' => DeliveryAgent::TYPES,
            'filters' => $request->only(['q', 'type', 'active']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('delivery.create');
        $company = Workspace::company();

        return view('delivery.create', [
            'types' => DeliveryAgent::TYPES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('delivery.create');
        $agent = $this->agents->create($this->validated($request));

        return redirect()->route('delivery.show', $agent)->with('success', 'Livreur créé.');
    }

    public function show(Request $request, DeliveryAgent $delivery): View
    {
        $this->authorize('delivery.view');
        $this->ensureCompany($delivery);
        $from = $request->input('from');
        $to = $request->input('to');
        $stats = $this->agents->performance($delivery, $from, $to);

        return view('delivery.show', [
            'agent' => $delivery->load('store'),
            'stats' => $stats,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function edit(DeliveryAgent $delivery): View
    {
        $this->authorize('delivery.update');
        $this->ensureCompany($delivery);
        $company = Workspace::company();

        return view('delivery.edit', [
            'agent' => $delivery,
            'types' => DeliveryAgent::TYPES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, DeliveryAgent $delivery): RedirectResponse
    {
        $this->authorize('delivery.update');
        $this->ensureCompany($delivery);
        $this->agents->update($delivery, $this->validated($request));

        return redirect()->route('delivery.show', $delivery)->with('success', 'Livreur mis à jour.');
    }

    public function destroy(DeliveryAgent $delivery): RedirectResponse
    {
        $this->authorize('delivery.update');
        $this->ensureCompany($delivery);
        $this->agents->softDelete($delivery);

        return redirect()->route('delivery.index')->with('success', 'Livreur archivé.');
    }

    public function ranking(Request $request): View
    {
        $this->authorize('delivery.view');
        $company = Workspace::company();
        $from = $request->input('from');
        $to = $request->input('to');
        $ranking = $this->agents->ranking($company->id, $from, $to);

        return view('delivery.ranking', [
            'ranking' => $ranking,
            'filters' => ['from' => $from, 'to' => $to],
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:internal,external'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'employee_id' => ['nullable', 'integer'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_fixed' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'platform_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    protected function ensureCompany(DeliveryAgent $agent): void
    {
        if ($agent->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}
