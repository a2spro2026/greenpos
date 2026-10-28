<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAutomation;
use App\Services\CrmAutomationService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function __construct(private CrmAutomationService $automations)
    {
    }

    public function index(): View
    {
        $this->authorize('customers.view');
        $items = CrmAutomation::query()->forCompany(Workspace::company()->id)->latest()->paginate(20);

        return view('crm.automations.index', [
            'items' => $items,
            'triggers' => CrmAutomation::TRIGGERS,
        ]);
    }

    public function create(): View
    {
        $this->authorize('customers.create');

        return view('crm.automations.create', ['triggers' => CrmAutomation::TRIGGERS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('customers.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'trigger_type' => ['required', 'in:low_stock,sales_threshold,production,time_based,custom'],
            'is_active' => ['sometimes', 'boolean'],
            'action_type' => ['required', 'in:create_incident,create_task,send_reminder,notify'],
            'action_title' => ['nullable', 'string', 'max:200'],
            'action_message' => ['nullable', 'string', 'max:2000'],
            'action_channel' => ['nullable', 'in:email,sms,whatsapp,notification'],
            'action_priority' => ['nullable', 'in:low,medium,urgent'],
        ]);

        CrmAutomation::query()->create([
            'company_id' => Workspace::company()->id,
            'name' => $data['name'],
            'trigger_type' => $data['trigger_type'],
            'is_active' => $request->boolean('is_active', true),
            'conditions' => ['force' => true],
            'actions' => [[
                'type' => $data['action_type'],
                'title' => $data['action_title'] ?? $data['name'],
                'message' => $data['action_message'] ?? null,
                'body' => $data['action_message'] ?? null,
                'channel' => $data['action_channel'] ?? 'notification',
                'priority' => $data['action_priority'] ?? 'medium',
                'recipient' => Workspace::user()?->email,
            ]],
        ]);

        return redirect()->route('crm.automations.index')->with('success', 'Automatisation créée.');
    }

    public function run(CrmAutomation $automation): RedirectResponse
    {
        $this->authorize('customers.update');
        abort_unless($automation->company_id === Workspace::company()->id, 404);
        $this->automations->run($automation);

        return back()->with('success', 'Automatisation exécutée.');
    }

    public function destroy(CrmAutomation $automation): RedirectResponse
    {
        $this->authorize('customers.delete');
        abort_unless($automation->company_id === Workspace::company()->id, 404);
        $automation->delete();

        return back()->with('success', 'Automatisation supprimée.');
    }
}
