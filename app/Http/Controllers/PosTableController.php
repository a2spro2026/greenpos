<?php

namespace App\Http\Controllers;

use App\Models\PosTable;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosTableController extends Controller
{
    public function index(): View
    {
        $this->authorize('pos.view');
        $company = Workspace::company();
        $store = Workspace::store();
        $tables = PosTable::query()
            ->forCompany($company->id)
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pos.tables.index', compact('tables'));
    }

    public function floor(): View
    {
        $this->authorize('pos.view');
        $company = Workspace::company();
        $store = Workspace::store();
        $tables = PosTable::query()
            ->forCompany($company->id)
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->with('currentSale')
            ->orderBy('sort_order')
            ->get();

        return view('pos.tables.floor', compact('tables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('pos.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'group_name' => ['nullable', 'string', 'max:64'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $store = Workspace::store();
        abort_unless($store, 422, 'Boutique requise');

        PosTable::query()->create([
            ...$data,
            'company_id' => Workspace::company()->id,
            'store_id' => $store->id,
            'seats' => $data['seats'] ?? 4,
            'status' => 'free',
        ]);

        return back()->with('success', 'Table créée.');
    }

    public function destroy(PosTable $table): RedirectResponse
    {
        $this->authorize('pos.delete');
        abort_unless($table->company_id === Workspace::company()->id, 404);
        $table->delete();

        return back()->with('success', 'Table supprimée.');
    }
}
