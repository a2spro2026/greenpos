<?php

namespace App\Http\Controllers;

use App\Services\ModuleManagerService;
use App\Support\ModuleCatalog;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleManagerController extends Controller
{
    public function __construct(private ModuleManagerService $modules)
    {
    }

    public function index(Request $request): View
    {
        abort_unless(Workspace::canIgnoringModules('settings.view') || Workspace::role() === 'owner', 403);

        $company = Workspace::company();
        $this->modules->ensureSynced($company);

        $filters = [
            'q' => $request->string('q')->toString(),
            'category' => $request->string('category')->toString() ?: 'Tous',
            'status' => $request->string('status')->toString(),
        ];

        $catalog = $this->modules->catalogForCompany(
            $company,
            $filters['q'] ?: null,
            $filters['category'] !== 'Tous' ? $filters['category'] : null,
            $filters['status'] ?: null,
        );

        $plan = $this->modules->planForCompany($company);
        $stats = $this->modules->storeStats($company);

        return view('modules.index', [
            'catalog' => $catalog,
            'filtersList' => ModuleCatalog::storeFilters(),
            'plan' => $plan,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function show(string $module): View
    {
        abort_unless(Workspace::canIgnoringModules('settings.view') || Workspace::role() === 'owner', 403);

        $company = Workspace::company();
        $detail = $this->modules->detailForCompany($module, $company);
        abort_unless($detail, 404);

        return view('modules.show', [
            'mod' => $detail,
            'plan' => $this->modules->planForCompany($company),
        ]);
    }

    public function toggle(Request $request, string $module): RedirectResponse
    {
        abort_unless(Workspace::canIgnoringModules('settings.view') || Workspace::role() === 'owner', 403);

        $company = Workspace::company();
        abort_unless($company, 403);

        $enable = $request->boolean('enable');
        $this->modules->toggleModule($module, $company, $enable);

        $name = ModuleCatalog::get($module)['name'] ?? $module;

        return back()->with(
            'success',
            $enable
                ? "Le module « {$name} » a été activé."
                : "Le module « {$name} » a été désactivé."
        );
    }
}
