<?php

namespace App\Http\Controllers;

use App\Models\DeliveryPlatform;
use App\Services\CustomListService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeliveryPlatformController extends Controller
{
    public function __construct(private CustomListService $lists)
    {
    }

    public function index(): View
    {
        $this->authorize('settings.delivery_platforms');
        $platforms = DeliveryPlatform::query()->forCompany(Workspace::company()->id)->orderBy('name')->get();

        return view('settings.delivery-platforms.index', compact('platforms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.delivery_platforms');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:internal,external'],
            'commission_type' => ['required', 'in:percent,fixed'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
        ]);
        $companyId = Workspace::company()->id;
        $platform = DeliveryPlatform::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $this->code($companyId, $data['name']),
            'kind' => $data['kind'],
            'is_active' => $request->boolean('is_active', true),
            'is_delivery_agent' => $request->boolean('is_delivery_agent', true),
            'commission_type' => $data['commission_type'],
            'commission_value' => $data['commission_value'] ?? 0,
        ]);
        $this->lists->syncPlatformServiceMode($platform);

        return back()->with('success', 'Plateforme enregistrée et synchronisée dans les modes de service.');
    }

    public function update(Request $request, DeliveryPlatform $platform): RedirectResponse
    {
        $this->authorize('settings.delivery_platforms');
        abort_unless((int) $platform->company_id === (int) Workspace::company()?->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:internal,external'],
            'commission_type' => ['required', 'in:percent,fixed'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
        ]);
        $platform->update([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'is_active' => $request->boolean('is_active'),
            'is_delivery_agent' => $request->boolean('is_delivery_agent'),
            'commission_type' => $data['commission_type'],
            'commission_value' => $data['commission_value'] ?? 0,
        ]);
        $this->lists->syncPlatformServiceMode($platform->fresh());

        return back()->with('success', 'Plateforme synchronisée avec les modes de service.');
    }

    private function code(int $companyId, string $name): string
    {
        $base = Str::slug($name) ?: 'plateforme';
        $code = $base;
        $i = 2;
        while (DeliveryPlatform::query()->forCompany($companyId)->where('code', $code)->exists()) {
            $code = $base.'-'.$i++;
        }

        return $code;
    }
}
