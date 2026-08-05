<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\SaasService;
use App\Support\ModuleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        app(SaasService::class)->ensurePlans();
        $catalog = ModuleCatalog::all();
        $plans = SaasPlan::query()->orderBy('sort_order')->get();

        return view('admin.modules.index', compact('catalog', 'plans'));
    }

    public function updatePlan(Request $request, SaasPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

        $keys = array_values(array_intersect($data['modules'] ?? [], ModuleCatalog::keys()));
        $plan->update(['modules' => $keys]);

        return back()->with('success', 'Modules du plan '.$plan->name.' mis à jour.');
    }
}
