<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\SalePayment;
use App\Services\CollectionService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __construct(private CollectionService $collections)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('accounting.view');
        $company = Workspace::company();
        $query = $this->collections->pendingQuery($company->id);

        if ($request->string('filter') === 'overdue') {
            $query->where('due_at', '<', now())->where('status', 'pending');
        } elseif ($request->string('filter') === 'today') {
            $query->whereDate('due_at', today());
        }

        $items = $query->orderBy('due_at')->paginate(25);

        return view('accounting.collections.index', [
            'items' => $items,
            'filter' => $request->string('filter')->toString(),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function collect(Request $request, SalePayment $payment): RedirectResponse
    {
        $this->authorize('accounting.update');
        abort_unless($payment->sale?->company_id === Workspace::company()->id, 404);
        $this->collections->markCollected($payment, $request->validate(['notes' => ['nullable', 'string']]));

        return back()->with('success', 'Paiement encaissé.');
    }

    public function reschedule(Request $request, SalePayment $payment): RedirectResponse
    {
        $this->authorize('accounting.update');
        abort_unless($payment->sale?->company_id === Workspace::company()->id, 404);
        $data = $request->validate(['due_at' => ['required', 'date']]);
        $this->collections->reschedule($payment, $data['due_at']);

        return back()->with('success', 'Échéance reportée.');
    }

    public function cancel(Request $request, SalePayment $payment): RedirectResponse
    {
        $this->authorize('accounting.update');
        abort_unless($payment->sale?->company_id === Workspace::company()->id, 404);
        $this->collections->cancel($payment, $request->input('notes'));

        return back()->with('success', 'Paiement différé annulé.');
    }
}
