<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmIncident;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('customers.view');
        $company = Workspace::company();
        $items = CrmIncident::query()
            ->forCompany($company->id)
            ->with(['assignee', 'employee'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20);

        $stats = [
            'open' => CrmIncident::query()->forCompany($company->id)->where('status', 'open')->count(),
            'urgent' => CrmIncident::query()->forCompany($company->id)->where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved' => CrmIncident::query()->forCompany($company->id)->where('status', 'resolved')->count(),
        ];

        return view('crm.incidents.index', [
            'items' => $items,
            'stats' => $stats,
            'statuses' => CrmIncident::STATUSES,
            'priorities' => CrmIncident::PRIORITIES,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('customers.create');

        return view('crm.incidents.create', [
            'priorities' => CrmIncident::PRIORITIES,
            'users' => User::query()->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('customers.create');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'type' => ['nullable', 'string', 'max:64'],
            'priority' => ['required', 'in:low,medium,urgent'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        CrmIncident::query()->create([
            ...$data,
            'company_id' => Workspace::company()->id,
            'store_id' => Workspace::store()?->id,
            'status' => 'open',
            'created_by' => Workspace::user()?->id,
        ]);

        return redirect()->route('crm.incidents.index')->with('success', 'Incident créé.');
    }

    public function updateStatus(Request $request, CrmIncident $incident): RedirectResponse
    {
        $this->authorize('customers.update');
        abort_unless($incident->company_id === Workspace::company()->id, 404);
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,resolved,cancelled']]);
        $incident->update([
            'status' => $data['status'],
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        return back()->with('success', 'Statut mis à jour.');
    }
}
