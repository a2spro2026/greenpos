<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmActivity;
use App\Models\CrmEmailLog;
use App\Models\CrmEmailTemplate;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\Customer;
use App\Models\User;
use App\Services\CrmService;
use App\Support\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CrmController extends Controller
{
    public function __construct(private CrmService $crm)
    {
    }

    public function dashboard(): View
    {
        $this->crm->seedDemoIfEmpty();
        $stats = $this->crm->dashboardStats();

        return view('crm.dashboard', compact('stats'));
    }

    public function pipeline(): View
    {
        $this->crm->seedDemoIfEmpty();
        $columns = $this->crm->pipelineBoard();

        return view('crm.pipeline', compact('columns'));
    }

    public function move(Request $request, CrmOpportunity $opportunity): JsonResponse
    {
        $data = $request->validate([
            'stage' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $opp = $this->crm->moveOpportunity($opportunity, $data['stage'], $data['order'] ?? null);

        return response()->json([
            'ok' => true,
            'opportunity' => [
                'id' => $opp->id,
                'stage' => $opp->stage,
                'probability' => $opp->probability,
                'stage_label' => $opp->stageLabel(),
            ],
        ]);
    }

    public function leadsIndex(Request $request): View
    {
        $this->crm->seedDemoIfEmpty();
        $cid = $this->crm->companyId();
        $leads = CrmLead::query()->forCompany($cid)
            ->with('owner')
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when(! $request->boolean('archived'), fn ($q) => $q->whereNull('archived_at'))
            ->when($request->boolean('archived'), fn ($q) => $q->whereNotNull('archived_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('crm.leads.index', [
            'leads' => $leads,
            'statuses' => CrmLead::STATUSES,
            'types' => CrmLead::TYPES,
            'filters' => $request->only(['q', 'status', 'type', 'archived']),
        ]);
    }

    public function leadsCreate(): View
    {
        return view('crm.leads.create', [
            'users' => $this->workspaceUsers(),
            'sources' => CrmLead::SOURCES,
            'ratings' => CrmLead::RATINGS,
            'types' => CrmLead::TYPES,
        ]);
    }

    public function leadsStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:prospect,lead'],
            'source' => ['nullable', 'string', 'max:64'],
            'rating' => ['nullable', 'string', 'max:16'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'mobile' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:2'],
            'website' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $lead = $this->crm->createLead($data);

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead créé.');
    }

    public function leadsShow(CrmLead $lead): View
    {
        abort_unless($lead->company_id === $this->crm->companyId(), 403);
        $lead->load(['owner', 'customer', 'opportunities', 'activities' => fn ($q) => $q->latest()->limit(30)]);
        $summary = $this->crm->summarizeLead($lead);

        return view('crm.leads.show', [
            'lead' => $lead,
            'summary' => $summary,
            'users' => $this->workspaceUsers(),
        ]);
    }

    public function leadsEdit(CrmLead $lead): View
    {
        abort_unless($lead->company_id === $this->crm->companyId(), 403);

        return view('crm.leads.edit', [
            'lead' => $lead,
            'users' => $this->workspaceUsers(),
            'sources' => CrmLead::SOURCES,
            'ratings' => CrmLead::RATINGS,
            'types' => CrmLead::TYPES,
            'statuses' => CrmLead::STATUSES,
        ]);
    }

    public function leadsUpdate(Request $request, CrmLead $lead): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:prospect,lead'],
            'status' => ['required', 'string'],
            'source' => ['nullable', 'string', 'max:64'],
            'rating' => ['nullable', 'string', 'max:16'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'mobile' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:2'],
            'website' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $this->crm->updateLead($lead, $data);

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Lead mis à jour.');
    }

    public function leadsQualify(CrmLead $lead): RedirectResponse
    {
        $this->crm->qualifyLead($lead);

        return back()->with('success', 'Lead qualifié.');
    }

    public function leadsAssign(Request $request, CrmLead $lead): RedirectResponse
    {
        $data = $request->validate(['owner_user_id' => ['required', 'exists:users,id']]);
        $this->crm->assignLead($lead, (int) $data['owner_user_id']);

        return back()->with('success', 'Lead affecté.');
    }

    public function leadsConvert(Request $request, CrmLead $lead): RedirectResponse
    {
        $result = $this->crm->convertLead($lead, $request->boolean('create_opportunity', true));

        return redirect()
            ->route('crm.leads.show', $result['lead'])
            ->with('success', 'Lead converti en client'.($result['opportunity'] ? ' + opportunité créée' : '').'.');
    }

    public function leadsArchive(CrmLead $lead): RedirectResponse
    {
        $this->crm->archiveLead($lead);

        return redirect()->route('crm.leads.index')->with('success', 'Lead archivé.');
    }

    public function opportunitiesIndex(Request $request): View
    {
        $cid = $this->crm->companyId();
        $opps = CrmOpportunity::query()->forCompany($cid)
            ->with(['owner', 'lead', 'customer'])
            ->when($request->filled('stage'), fn ($q) => $q->where('stage', $request->string('stage')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->string('q').'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $t)->orWhere('number', 'like', $t));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('crm.opportunities.index', [
            'opportunities' => $opps,
            'stages' => CrmOpportunity::STAGES,
            'filters' => $request->only(['q', 'stage']),
        ]);
    }

    public function opportunitiesCreate(Request $request): View
    {
        return view('crm.opportunities.create', [
            'leads' => CrmLead::query()->forCompany($this->crm->companyId())->whereNull('archived_at')->orderBy('company_name')->get(),
            'customers' => Customer::query()->where('company_id', $this->crm->companyId())->orderBy('name')->limit(200)->get(),
            'users' => $this->workspaceUsers(),
            'stages' => CrmOpportunity::STAGES,
            'lead_id' => $request->integer('lead_id') ?: null,
        ]);
    }

    public function opportunitiesStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'crm_lead_id' => ['nullable', 'exists:crm_leads,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'stage' => ['required', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'expected_close_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $opp = $this->crm->createOpportunity($data);

        return redirect()->route('crm.pipeline')->with('success', 'Opportunité créée.');
    }

    public function opportunitiesShow(CrmOpportunity $opportunity): View
    {
        abort_unless($opportunity->company_id === $this->crm->companyId(), 403);
        $opportunity->load(['owner', 'lead', 'customer', 'quote', 'invoice', 'activities' => fn ($q) => $q->latest()]);
        $estimate = $this->crm->estimateWinProbability($opportunity);

        return view('crm.opportunities.show', compact('opportunity', 'estimate'));
    }

    public function activitiesIndex(Request $request): View
    {
        $cid = $this->crm->companyId();
        $activities = CrmActivity::query()->forCompany($cid)
            ->with(['owner', 'lead', 'opportunity'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('crm.activities.index', [
            'activities' => $activities,
            'types' => CrmActivity::TYPES,
            'statuses' => CrmActivity::STATUSES,
            'filters' => $request->only(['type', 'status']),
        ]);
    }

    public function activitiesCreate(Request $request): View
    {
        $cid = $this->crm->companyId();

        return view('crm.activities.create', [
            'types' => CrmActivity::TYPES,
            'leads' => CrmLead::query()->forCompany($cid)->whereNull('archived_at')->latest()->limit(100)->get(),
            'opportunities' => CrmOpportunity::query()->forCompany($cid)->whereNotIn('stage', ['won', 'lost'])->latest()->limit(100)->get(),
            'lead_id' => $request->integer('lead_id') ?: null,
            'opportunity_id' => $request->integer('opportunity_id') ?: null,
        ]);
    }

    public function activitiesStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:call,email,meeting,task,follow_up,note'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'crm_lead_id' => ['nullable', 'exists:crm_leads,id'],
            'crm_opportunity_id' => ['nullable', 'exists:crm_opportunities,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $activity = $this->crm->createActivity($data);

        return redirect()->route('crm.activities.show', $activity)->with('success', 'Activité créée.');
    }

    public function activitiesShow(CrmActivity $activity): View
    {
        abort_unless($activity->company_id === $this->crm->companyId(), 403);
        $activity->load(['owner', 'lead', 'opportunity', 'customer']);

        return view('crm.activities.show', compact('activity'));
    }

    public function activitiesComplete(CrmActivity $activity): RedirectResponse
    {
        $this->crm->completeActivity($activity);

        return back()->with('success', 'Activité terminée.');
    }

    public function calendar(Request $request): View
    {
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->endOfMonth()->toDateString();
        $events = $this->crm->calendarEvents($from, $to);

        return view('crm.calendar', compact('events', 'from', 'to'));
    }

    public function emailsIndex(): View
    {
        $this->crm->ensureEmailTemplates();
        $cid = $this->crm->companyId();

        return view('crm.emails.index', [
            'templates' => CrmEmailTemplate::query()->where('company_id', $cid)->orderBy('name')->get(),
            'logs' => CrmEmailLog::query()->where('company_id', $cid)->latest()->limit(40)->with(['lead', 'template'])->get(),
        ]);
    }

    public function emailsStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'crm_email_template_id' => ['nullable', 'exists:crm_email_templates,id'],
            'crm_lead_id' => ['nullable', 'exists:crm_leads,id'],
            'to_email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $this->crm->logEmail($data);

        return back()->with('success', 'Email enregistré (suivi prêt).');
    }

    public function emailTrack(string $token): Response
    {
        $this->crm->markEmailOpened($token);
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store']);
    }

    public function reports(): View
    {
        $stats = $this->crm->reportStats();

        return view('crm.reports', compact('stats'));
    }

    public function aiAssist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:summarize_lead,estimate_opportunity,draft_email'],
            'lead_id' => ['nullable', 'exists:crm_leads,id'],
            'opportunity_id' => ['nullable', 'exists:crm_opportunities,id'],
        ]);

        if ($data['action'] === 'summarize_lead') {
            $lead = CrmLead::query()->findOrFail($data['lead_id']);
            abort_unless($lead->company_id === $this->crm->companyId(), 403);

            return response()->json(['content' => $this->crm->summarizeLead($lead)]);
        }

        if ($data['action'] === 'estimate_opportunity') {
            $opp = CrmOpportunity::query()->findOrFail($data['opportunity_id']);
            abort_unless($opp->company_id === $this->crm->companyId(), 403);

            return response()->json($this->crm->estimateWinProbability($opp));
        }

        $lead = CrmLead::query()->findOrFail($data['lead_id']);
        abort_unless($lead->company_id === $this->crm->companyId(), 403);
        $name = $lead->displayName();
        $content = "Objet : Suite à notre échange — {$name}\n\n"
            ."Bonjour {$name},\n\n"
            ."Suite à votre intérêt pour GreenPOS, je vous propose un créneau cette semaine pour finaliser votre besoin.\n\n"
            .'Probabilité estimée de conversion : forte si une démo est planifiée sous 7 jours.'."\n\n"
            ."Cordialement,\nL’équipe commerciale";

        return response()->json(['content' => $content]);
    }

    protected function workspaceUsers()
    {
        $cid = $this->crm->companyId();

        return User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $cid))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
