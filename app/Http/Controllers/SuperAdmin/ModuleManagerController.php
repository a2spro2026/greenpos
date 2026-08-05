<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\ModuleManagerService;
use App\Services\SaasSubscriptionService;
use App\Support\ModuleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleManagerController extends Controller
{
    public function __construct(private ModuleManagerService $modules)
    {
    }

    public function index(): View
    {
        app(SaasSubscriptionService::class)->syncPlanCatalog();
        $this->modules->bootstrapPlans();

        $plans = SaasPlan::query()->orderBy('sort_order')->get();
        $catalog = ModuleCatalog::all();

        return view('superadmin.modules.index', [
            'plans' => $plans,
            'catalog' => $catalog,
            'categories' => ModuleCatalog::categories(),
        ]);
    }

    public function updatePlan(Request $request, SaasPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

        $this->modules->updatePlanModules($plan, $data['modules'] ?? []);

        return back()->with('success', 'Modules du plan '.$plan->name.' mis à jour.');
    }
}
