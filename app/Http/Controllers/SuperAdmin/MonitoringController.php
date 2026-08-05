<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlatformSnapshot;
use App\Services\SaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function index(): View
    {
        $latest = SaasPlatformSnapshot::query()->latest('captured_at')->first()
            ?? $this->saas->capturePlatformSnapshot();

        $history = SaasPlatformSnapshot::query()
            ->orderByDesc('captured_at')
            ->limit(24)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (SaasPlatformSnapshot $s) => [
                'label' => $s->captured_at->format('d/m H:i'),
                'cpu_percent' => (float) $s->cpu_percent,
                'memory_percent' => (float) $s->memory_percent,
                'disk_percent' => (float) $s->disk_percent,
            ]);

        return view('superadmin.monitoring.index', compact('latest', 'history'));
    }

    public function refresh(): RedirectResponse
    {
        $this->saas->capturePlatformSnapshot();

        return back()->with('success', 'Snapshot plateforme capturé.');
    }
}
