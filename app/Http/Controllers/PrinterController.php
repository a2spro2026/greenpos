<?php

namespace App\Http\Controllers;

use App\Models\Printer;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function index(): View
    {
        $this->authorize('pos.printers');
        $printers = Printer::query()->forCompany(Workspace::company()->id)->with('store')->orderByDesc('is_default')->orderBy('name')->get();

        return view('pos.printers.index', compact('printers'));
    }

    public function create(): View
    {
        $this->authorize('pos.printers');
        $defaults = Printer::defaultConfigs();

        return view('pos.printers.form', [
            'printer' => new Printer(array_merge(['role' => 'both', 'is_active' => true], $defaults)),
            'stores' => Workspace::company()->stores()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('pos.printers');
        $data = $this->payload($request);
        $data['company_id'] = Workspace::company()->id;
        $printer = Printer::query()->create($data);
        $this->promoteDefault($printer);

        return redirect()->route('pos.printers.index')->with('success', 'Profil d\'impression enregistré.');
    }

    public function edit(Printer $printer): View
    {
        $this->authorize('pos.printers');
        abort_unless((int) $printer->company_id === (int) Workspace::company()?->id, 404);

        return view('pos.printers.form', [
            'printer' => $printer,
            'stores' => Workspace::company()->stores()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Printer $printer): RedirectResponse
    {
        $this->authorize('pos.printers');
        abort_unless((int) $printer->company_id === (int) Workspace::company()?->id, 404);
        $printer->update($this->payload($request));
        $this->promoteDefault($printer->fresh());

        return redirect()->route('pos.printers.index')->with('success', 'Profil d\'impression mis à jour.');
    }

    private function payload(Request $request): array
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:customer,kitchen,both'],
            'store_id' => ['nullable', 'integer'],
            'copies' => ['nullable', 'integer', 'min:1', 'max:5'],
            'paper_width' => ['nullable', 'integer', 'in:58,80'],
        ]);
        $defaults = Printer::defaultConfigs();

        return [
            'store_id' => $request->input('store_id') ?: null,
            'name' => $request->string('name')->toString(),
            'role' => $request->string('role')->toString(),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
            'ticket_config' => array_merge($defaults['ticket_config'], [
                'show_logo' => $request->boolean('show_logo'),
                'show_ice' => $request->boolean('show_ice'),
                'show_qr' => $request->boolean('show_qr'),
                'header' => $request->string('ticket_header')->toString(),
                'footer' => $request->string('ticket_footer')->toString() ?: 'Merci de votre visite',
                'auto_print' => $request->boolean('ticket_auto_print'),
            ]),
            'kitchen_config' => array_merge($defaults['kitchen_config'], [
                'group_by_category' => $request->boolean('group_by_category'),
                'show_prices' => $request->boolean('show_prices'),
                'auto_print' => $request->boolean('kitchen_auto_print'),
                'header' => $request->string('kitchen_header')->toString() ?: 'CUISINE',
            ]),
            'advanced_config' => [
                'open_drawer' => $request->boolean('open_drawer'),
                'copies' => (int) $request->input('copies', 1),
                'paper_width' => (int) $request->input('paper_width', 80),
            ],
        ];
    }

    private function promoteDefault(Printer $printer): void
    {
        if (! $printer->is_default) {
            return;
        }
        Printer::query()
            ->where('company_id', $printer->company_id)
            ->whereKeyNot($printer->id)
            ->update(['is_default' => false]);
    }
}
