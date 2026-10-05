<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmIncident;
use App\Models\Customer;
use App\Models\IncidentTypeAssignment;
use App\Models\User;
use App\Services\IncidentAssignmentService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmIncidentController extends Controller
{
    public function __construct(private IncidentAssignmentService $assignments)
    {
    }

    public function index(): View
    {
        $this->authorize('crm.incidents');
        $cid = Workspace::company()->id;

        return view('crm.incidents.index', [
            'incidents' => CrmIncident::query()->forCompany($cid)->with(['assignee', 'customer'])->latest()->paginate(30),
            'stats' => $this->assignments->statistics($cid),
            'assignments' => IncidentTypeAssignment::query()->where('company_id', $cid)->with('user')->get()->keyBy('incident_type'),
            'types' => CrmIncident::TYPES,
            'users' => User::query()->forCompany($cid)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('crm.incidents');
        $cid = Workspace::company()->id;

        return view('crm.incidents.form', [
            'incident' => new CrmIncident(['priority' => 'normal', 'status' => 'open', 'type' => 'complaint']),
            'types' => CrmIncident::TYPES,
            'priorities' => CrmIncident::PRIORITIES,
            'customers' => Customer::query()->forCompany($cid)->orderBy('name')->limit(200)->get(),
            'users' => User::query()->forCompany($cid)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('crm.incidents');
        $data = $this->validated($request);
        $company = Workspace::company();
        $seq = CrmIncident::query()->forCompany($company->id)->count() + 1;
        $incident = CrmIncident::query()->create([
            'company_id' => $company->id,
            'store_id' => Workspace::store()?->id,
            'customer_id' => $data['customer_id'] ?? null,
            'assignee_user_id' => $data['assignee_user_id'] ?? null,
            'reported_by' => Workspace::user()?->id,
            'number' => 'INC-'.now()->format('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'type' => $data['type'],
            'priority' => $data['priority'],
            'status' => 'open',
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
        ]);
        $this->assignments->assignIfEmpty($incident);

        return redirect()->route('crm.incidents.show', $incident)->with('success', 'Incident ouvert.');
    }

    public function show(CrmIncident $incident): View
    {
        $this->authorize('crm.incidents');
        abort_unless((int) $incident->company_id === (int) Workspace::company()?->id, 404);
        $incident->load(['assignee', 'customer', 'reporter', 'store']);

        return view('crm.incidents.show', [
            'incident' => $incident,
            'statuses' => CrmIncident::STATUSES,
        ]);
    }

    public function update(Request $request, CrmIncident $incident): RedirectResponse
    {
        $this->authorize('crm.incidents');
        abort_unless((int) $incident->company_id === (int) Workspace::company()?->id, 404);
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'assignee_user_id' => ['nullable', 'exists:users,id'],
        ]);
        $incident->update([
            'status' => $data['status'],
            'assignee_user_id' => $data['assignee_user_id'] ?? $incident->assignee_user_id,
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? ($incident->resolved_at ?? now()) : null,
        ]);

        return back()->with('success', 'Incident mis à jour.');
    }

    public function assignType(Request $request): RedirectResponse
    {
        $this->authorize('crm.incidents');
        $data = $request->validate([
            'incident_type' => ['required', 'in:'.implode(',', array_keys(CrmIncident::TYPES))],
            'user_id' => ['required', 'exists:users,id'],
        ]);
        IncidentTypeAssignment::query()->updateOrCreate(
            ['company_id' => Workspace::company()->id, 'incident_type' => $data['incident_type']],
            ['user_id' => $data['user_id'], 'is_active' => true]
        );

        return back()->with('success', 'Affectation automatique enregistrée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(CrmIncident::TYPES))],
            'priority' => ['required', 'in:'.implode(',', array_keys(CrmIncident::PRIORITIES))],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'assignee_user_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
