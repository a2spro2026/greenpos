<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditService;
use App\Support\PermissionCatalog;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('audit.view');
        $this->audit->seedDemo();
        $stats = $this->audit->dashboardStats();

        return view('audit.dashboard', compact('stats'));
    }

    public function index(Request $request): View
    {
        $this->authorize('audit.view');
        $this->audit->seedDemo();

        $company = Workspace::company();
        $query = $this->filteredQuery($request);

        if ($request->filled('severity') && $request->string('severity')->toString() === 'critical') {
            $this->authorize('audit.critical');
        } elseif (! auth()->user()->can('audit.critical')) {
            $query->where('severity', '!=', 'critical');
        }

        $sort = $request->string('sort', 'occurred_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['occurred_at', 'module', 'action', 'severity', 'result', 'ip_address'], true)) {
            $sort = 'occurred_at';
        }

        $events = $query->with(['user', 'store', 'company'])
            ->orderBy($sort, $dir)
            ->paginate(25)
            ->withQueryString();

        $users = User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'first_name', 'last_name']);

        $stores = Store::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);
        $companies = Workspace::accessibleCompanies();

        return view('audit.index', [
            'events' => $events,
            'users' => $users,
            'stores' => $stores,
            'companies' => $companies,
            'actions' => AuditEvent::ACTIONS,
            'severities' => AuditEvent::SEVERITIES,
            'eventTypes' => AuditEvent::EVENT_TYPES,
            'results' => AuditEvent::RESULTS,
            'modules' => PermissionCatalog::MODULES,
            'filters' => $request->only([
                'q', 'user_id', 'module', 'action', 'from', 'to', 'store_id', 'company_id',
                'ip', 'event_type', 'severity', 'result', 'sort', 'dir',
            ]),
        ]);
    }

    public function show(AuditEvent $audit): View
    {
        $this->authorize('audit.view');
        $this->assertCompanyAccess($audit);

        if ($audit->isCritical()) {
            $this->authorize('audit.critical');
        }

        $audit->load(['user', 'store', 'company']);

        $sessionDuration = null;
        if ($audit->user_id && $audit->action === 'logout') {
            $login = AuditEvent::query()
                ->where('user_id', $audit->user_id)
                ->where('action', 'login')
                ->where('occurred_at', '<', $audit->occurred_at)
                ->orderByDesc('occurred_at')
                ->first();
            if ($login) {
                $sessionDuration = $login->occurred_at->diffForHumans($audit->occurred_at, true);
            }
        }

        return view('audit.show', [
            'event' => $audit,
            'sessionDuration' => $sessionDuration,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('audit.export');

        $query = $this->filteredQuery($request);
        if (! auth()->user()->can('audit.critical')) {
            $query->where('severity', '!=', 'critical');
        }

        $rows = $query->with(['user', 'store', 'company'])->orderByDesc('occurred_at')->limit(5000)->get();

        $this->audit->log([
            'module' => 'audit',
            'action' => 'export',
            'event_type' => 'system',
            'severity' => 'warning',
            'description' => 'Export journal d\'audit ('.$rows->count().' lignes)',
        ]);

        $filename = 'audit-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'Date', 'Heure', 'Utilisateur', 'Entreprise', 'Boutique', 'Module', 'Action',
                'Type', 'Criticité', 'Élément', 'IP', 'Appareil', 'Navigateur', 'OS', 'Résultat', 'Description',
            ], ';');

            foreach ($rows as $e) {
                fputcsv($out, [
                    $e->occurred_at?->format('d/m/Y'),
                    $e->occurred_at?->format('H:i:s'),
                    $e->user?->displayName() ?? 'Système',
                    $e->company?->name,
                    $e->store?->name,
                    $e->module,
                    $e->actionLabel(),
                    $e->eventTypeLabel(),
                    $e->severityLabel(),
                    $e->subject_label,
                    $e->ip_address,
                    $e->device,
                    $e->browser,
                    $e->platform,
                    $e->resultLabel(),
                    $e->description,
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request): View
    {
        $this->authorize('audit.export');

        $query = $this->filteredQuery($request);
        if (! auth()->user()->can('audit.critical')) {
            $query->where('severity', '!=', 'critical');
        }

        $events = $query->with(['user', 'store', 'company'])->orderByDesc('occurred_at')->limit(200)->get();

        $this->audit->log([
            'module' => 'audit',
            'action' => 'export',
            'event_type' => 'system',
            'severity' => 'warning',
            'description' => 'Export PDF journal d\'audit',
        ]);

        return view('audit.pdf', compact('events'));
    }

    public function print(Request $request): View
    {
        $this->authorize('audit.print');

        $query = $this->filteredQuery($request);
        if (! auth()->user()->can('audit.critical')) {
            $query->where('severity', '!=', 'critical');
        }

        $events = $query->with(['user', 'store', 'company'])->orderByDesc('occurred_at')->limit(200)->get();

        $this->audit->log([
            'module' => 'audit',
            'action' => 'print',
            'event_type' => 'system',
            'severity' => 'info',
            'description' => 'Impression journal d\'audit',
        ]);

        return view('audit.print', compact('events'));
    }

    public function printOne(AuditEvent $audit): View
    {
        $this->authorize('audit.print');
        $this->assertCompanyAccess($audit);

        if ($audit->isCritical()) {
            $this->authorize('audit.critical');
        }

        $audit->load(['user', 'store', 'company']);

        return view('audit.print-one', ['event' => $audit]);
    }

    public function purgeForm(): View
    {
        $this->authorize('audit.purge');

        return view('audit.purge');
    }

    public function purge(Request $request): RedirectResponse
    {
        $this->authorize('audit.purge');

        $data = $request->validate([
            'days' => ['required', 'integer', 'min:30', 'max:3650'],
            'confirm' => ['accepted'],
        ]);

        $deleted = $this->audit->purgeOlderThan((int) $data['days']);

        $this->audit->log([
            'module' => 'audit',
            'action' => 'delete',
            'event_type' => 'security',
            'severity' => 'critical',
            'description' => "Purge des journaux > {$data['days']} jours ({$deleted} événements)",
            'new_values' => ['days' => $data['days'], 'deleted' => $deleted],
        ]);

        return redirect()
            ->route('audit.index')
            ->with('success', "{$deleted} événement(s) purgé(s).");
    }

    protected function filteredQuery(Request $request)
    {
        $companyId = Workspace::company()?->id;

        return AuditEvent::query()
            ->when(
                $request->filled('company_id') && auth()->user()->can('companies.view'),
                fn ($q) => $q->where('company_id', $request->integer('company_id')),
                fn ($q) => $q->forCompany($companyId)
            )
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('ip'), fn ($q) => $q->where('ip_address', 'like', '%'.$request->string('ip').'%'))
            ->when($request->filled('event_type'), fn ($q) => $q->where('event_type', $request->string('event_type')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->when($request->filled('result'), fn ($q) => $q->where('result', $request->string('result')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->date('to')));
    }

    protected function assertCompanyAccess(AuditEvent $audit): void
    {
        $companyId = Workspace::company()?->id;
        if ($audit->company_id && $companyId && (int) $audit->company_id !== (int) $companyId) {
            $accessible = Workspace::accessibleCompanies()->pluck('id')->all();
            if (! in_array((int) $audit->company_id, array_map('intval', $accessible), true)) {
                abort(403);
            }
        }
    }
}
