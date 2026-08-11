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

    public function setup(): View|RedirectResponse
    {
        $company = Workspace::company();
        abort_unless($company, 403);

        if (! $company->needsModuleSetup()) {
            return redirect()->route('home');
        }

        $this->modules->ensureSynced($company);
        $catalog = $this->modules->catalogForCompany($company)
            ->reject(fn ($m) => in_array($m['key'], ModuleCatalog::ALWAYS_ON, true))
            ->values();

        $grouped = $catalog->groupBy('category');
        $meta = ModuleCatalog::categoryMeta();
        $sections = collect(ModuleCatalog::categories())
            ->map(function (string $cat) use ($grouped, $meta) {
                $items = $grouped->get($cat, collect());

                return [
                    'key' => $cat,
                    'slug' => \Illuminate\Support\Str::slug($cat),
                    'emoji' => $meta[$cat]['emoji'] ?? '•',
                    'lead' => $meta[$cat]['lead'] ?? '',
                    'modules' => $items,
                    'available' => $items->filter(fn ($m) => $m['in_plan'] && empty($m['coming_soon']))->count(),
                ];
            })
            ->filter(fn ($section) => $section['modules']->isNotEmpty())
            ->values();

        return view('modules.setup', [
            'sections' => $sections,
            'plan' => $this->modules->planForCompany($company),
            'company' => $company,
            'availableCount' => $catalog->filter(fn ($m) => $m['in_plan'] && empty($m['coming_soon']))->count(),
        ]);
    }

    public function storeSetup(Request $request): RedirectResponse
    {
        $company = Workspace::company();
        abort_unless($company, 403);

        if (! $company->needsModuleSetup()) {
            return redirect()->route('home');
        }

        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

        $this->modules->applyModules($company, $data['modules'] ?? [], 'setup', true);

        return redirect()
            ->route('home')
            ->with('success', 'Vos modules sont activés. Bienvenue dans votre espace GreenPOS.');
    }

    public function index(Request $request): RedirectResponse
    {
        $company = Workspace::company();
        if ($company?->needsModuleSetup()) {
            return redirect()->route('modules.setup');
        }

        return redirect()
            ->route('home')
            ->with('warning', 'Pour ajouter un module, contactez le Super Admin GreenPOS.');
    }

    public function show(string $module): RedirectResponse
    {
        return $this->index(request());
    }

    public function toggle(Request $request, string $module): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->with('warning', 'Pour ajouter ou retirer un module, contactez le Super Admin GreenPOS.');
    }
}
