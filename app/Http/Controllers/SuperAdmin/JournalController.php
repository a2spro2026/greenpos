<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasAuditEvent;
use App\Models\SaasPlatformSnapshot;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use App\Models\User;
use App\Services\SaasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function index(Request $request): View
    {
        $this->saas->seedJournalIfEmpty();

        $events = SaasAuditEvent::query()
            ->with(['tenant', 'user'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)->orWhere('body', 'like', $term);
                });
            })
            ->latest('occurred_at')
            ->paginate(40)
            ->withQueryString();

        $stats = [
            'logins_24h' => SaasAuditEvent::query()->where('category', 'login')->where('occurred_at', '>=', now()->subDay())->count(),
            'errors_7d' => SaasAuditEvent::query()->where('category', 'error')->where('occurred_at', '>=', now()->subDays(7))->count(),
            'incidents_7d' => SaasAuditEvent::query()->where('category', 'incident')->where('occurred_at', '>=', now()->subDays(7))->count(),
            'tenants' => SaasTenant::query()->count(),
            'users' => User::query()->where('is_platform_admin', false)->count(),
            'subs' => SaasSubscription::query()->whereIn('status', ['active', 'trialing'])->count(),
            'latest_snapshot' => SaasPlatformSnapshot::query()->latest('captured_at')->first(),
        ];

        return view('superadmin.journal.index', [
            'events' => $events,
            'stats' => $stats,
            'categories' => SaasAuditEvent::CATEGORIES,
            'filters' => $request->only(['category', 'severity', 'q']),
        ]);
    }
}
