<?php

namespace App\Http\Controllers;

use App\Models\PosSession;
use App\Services\PosService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosSessionController extends Controller
{
    public function __construct(private PosService $pos)
    {
    }

    public function index(): View
    {
        $this->authorize('pos.view');
        $company = Workspace::company();

        $sessions = PosSession::query()
            ->forCompany($company->id)
            ->with(['store', 'opener', 'closer'])
            ->latest('opened_at')
            ->paginate(15);

        $current = $this->pos->currentOpenSession();

        return view('pos.sessions.index', compact('sessions', 'current'));
    }

    public function create(): View
    {
        $this->authorize('pos.open');

        return view('pos.sessions.create', [
            'current' => $this->pos->currentOpenSession(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('pos.open');
        $data = $request->validate([
            'opening_float' => ['nullable', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = $this->pos->openSession(
            (float) ($data['opening_float'] ?? 0),
            $data['opening_notes'] ?? null
        );

        return redirect()->route('pos.terminal')->with('success', 'Caisse '.$session->number.' ouverte.');
    }

    public function closeForm(PosSession $session): View
    {
        $this->authorize('pos.close');
        $this->ensure($session);
        if (! $session->isOpen()) {
            abort(403, 'Caisse déjà clôturée.');
        }

        $cashSales = (float) $session->sales()
            ->where('status', 'completed')
            ->with('payments')
            ->get()
            ->sum(fn ($sale) => $sale->payments->where('method', 'cash')->sum('amount'));

        $expected = (float) $session->opening_float + $cashSales;

        return view('pos.sessions.close', compact('session', 'expected', 'cashSales'));
    }

    public function close(Request $request, PosSession $session): RedirectResponse
    {
        $this->authorize('pos.close');
        $this->ensure($session);

        $data = $request->validate([
            'closing_counted' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->pos->closeSession($session, (float) $data['closing_counted'], $data['closing_notes'] ?? null);

        return redirect()->route('pos.sessions.index')->with('success', 'Caisse clôturée.');
    }

    public function show(PosSession $session): View
    {
        $this->authorize('pos.view');
        $this->ensure($session);
        $session->load(['store', 'opener', 'closer', 'sales' => fn ($q) => $q->latest()->limit(20)]);

        return view('pos.sessions.show', compact('session'));
    }

    protected function ensure(PosSession $session): void
    {
        if ($session->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}
