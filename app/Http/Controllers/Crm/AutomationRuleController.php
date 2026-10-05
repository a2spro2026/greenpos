<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Services\TaskAutomationService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationRuleController extends Controller
{
    public function __construct(private TaskAutomationService $automation)
    {
    }

    public function index(): View
    {
        $this->authorize('crm.automations');
        $company = Workspace::company();
        $this->automation->ensureDefaultRule($company);

        return view('crm.automations.index', [
            'rules' => AutomationRule::query()->forCompany($company->id)->orderBy('name')->get(),
            'triggers' => AutomationRule::TRIGGERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('crm.automations');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', 'in:'.implode(',', array_keys(AutomationRule::TRIGGERS))],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);
        AutomationRule::query()->create([
            'company_id' => Workspace::company()->id,
            'name' => $data['name'],
            'trigger' => $data['trigger'],
            'is_active' => $request->boolean('is_active', true),
            'config' => array_filter([
                'amount' => $data['amount'] ?? null,
                'hour' => $data['hour'] ?? null,
                'subject' => $data['subject'] ?? null,
                'dedupe_hours' => 24,
            ], fn ($v) => $v !== null && $v !== ''),
        ]);

        return back()->with('success', 'Règle enregistrée.');
    }

    public function update(Request $request, AutomationRule $rule): RedirectResponse
    {
        $this->authorize('crm.automations');
        abort_unless((int) $rule->company_id === (int) Workspace::company()?->id, 404);
        $rule->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Règle mise à jour.');
    }

    public function run(): RedirectResponse
    {
        $this->authorize('crm.automations');
        $result = $this->automation->runCompany(Workspace::company()->id);

        return back()->with('success', 'Automatisation exécutée : '.$result['tasks'].' tâche(s), '.$result['reminders'].' rappel(s), '.$result['recurrences'].' récurrence(s).');
    }
}
