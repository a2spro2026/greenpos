<?php

namespace App\Http\Controllers;

use App\Models\MeasureUnit;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeasureUnitController extends Controller
{
    public function index(): View
    {
        $this->authorize('products.measure_units');
        $units = MeasureUnit::query()->forCompany(Workspace::company()->id)->orderBy('sort_order')->orderBy('name')->get();

        return view('products.units.index', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('products.measure_units');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'symbol' => ['nullable', 'string', 'max:16'],
        ]);
        MeasureUnit::query()->create([
            'company_id' => Workspace::company()->id,
            'name' => $data['name'],
            'code' => $data['code'],
            'symbol' => $data['symbol'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Unité enregistrée. Le texte libre reste disponible sur la fiche produit.');
    }

    public function update(Request $request, MeasureUnit $measureUnit): RedirectResponse
    {
        $this->authorize('products.measure_units');
        abort_unless((int) $measureUnit->company_id === (int) Workspace::company()?->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'symbol' => ['nullable', 'string', 'max:16'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $measureUnit->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'symbol' => $data['symbol'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Unité mise à jour.');
    }
}
