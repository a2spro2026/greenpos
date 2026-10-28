<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Models\Sale;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PosKdsController extends Controller
{
    public function index(): View
    {
        $this->authorize('pos.view');
        $company = Workspace::company();
        $storeId = Workspace::store()?->id;

        $tickets = PosSale::query()
            ->forCompany($company->id)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('status', ['held', 'completed'])
            ->whereNull('archived_at')
            ->whereIn('service_mode', ['dine_in', 'takeaway', 'delivery'])
            ->where('created_at', '>=', now()->subHours(12))
            ->with('lines')
            ->latest()
            ->limit(40)
            ->get();

        $orders = Sale::query()
            ->forCompany($company->id)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('status', ['confirmed', 'preparing'])
            ->with('lines')
            ->latest()
            ->limit(40)
            ->get();

        return view('pos.kds', compact('tickets', 'orders'));
    }

    public function markReady(Sale $sale): RedirectResponse
    {
        $this->authorize('pos.update');
        abort_unless($sale->company_id === Workspace::company()->id, 404);
        $sale->update(['status' => 'delivered', 'delivered_at' => now()]);

        return back()->with('success', 'Commande prête.');
    }
}
