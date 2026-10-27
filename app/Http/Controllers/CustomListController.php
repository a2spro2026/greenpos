<?php

namespace App\Http\Controllers;

use App\Models\CustomList;
use App\Services\CustomListService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomListController extends Controller
{
    public function __construct(private CustomListService $lists)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('settings.lists');
        $company = Workspace::company();
        $this->lists->ensureDefaults($company);
        $type = $request->string('type')->toString();
        if (! array_key_exists($type, CustomList::TYPES)) {
            $type = 'mode_de_paiement';
        }

        $items = CustomList::query()
            ->forCompany($company->id)
            ->where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('settings.custom-lists.index', [
            'type' => $type,
            'types' => CustomList::TYPES,
            'items' => $items,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('settings.lists');
        $type = $request->string('type', 'mode_de_paiement')->toString();
        if (! array_key_exists($type, CustomList::TYPES)) {
            $type = 'mode_de_paiement';
        }

        return view('settings.custom-lists.form', [
            'list' => new CustomList(['type' => $type, 'is_active' => true, 'metadata' => []]),
            'types' => CustomList::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.lists');
        $data = $this->validateList($request);
        $company = Workspace::company();
        $data['company_id'] = $company->id;
        $data['code'] = $this->lists->uniqueCode($company->id, $data['type'], $data['code'] ?: $data['name']);
        $data['metadata'] = $this->metadata($request, $data['type']);
        CustomList::query()->create($data);

        return redirect()->route('settings.lists.index', ['type' => $data['type']])->with('success', 'Liste enregistrée.');
    }

    public function edit(CustomList $customList): View
    {
        $this->authorize('settings.lists');
        $this->owns($customList);

        return view('settings.custom-lists.form', [
            'list' => $customList,
            'types' => CustomList::TYPES,
        ]);
    }

    public function update(Request $request, CustomList $customList): RedirectResponse
    {
        $this->authorize('settings.lists');
        $this->owns($customList);
        $data = $this->validateList($request, $customList->id);
        $data['code'] = $this->lists->uniqueCode($customList->company_id, $data['type'], $data['code'] ?: $data['name'], $customList->id);
        $data['metadata'] = $this->metadata($request, $data['type']);
        $customList->update($data);

        return redirect()->route('settings.lists.index', ['type' => $customList->type])->with('success', 'Liste mise à jour.');
    }

    public function destroy(CustomList $customList): RedirectResponse
    {
        $this->authorize('settings.lists');
        $this->owns($customList);
        $type = $customList->type;
        $customList->update(['is_active' => false]);

        return redirect()->route('settings.lists.index', ['type' => $type])->with('success', 'Entrée désactivée.');
    }

    private function validateList(Request $request, ?int $ignoreId = null): array
    {
        $companyId = Workspace::company()->id;

        return $request->validate([
            'type' => ['required', Rule::in(array_keys(CustomList::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ]) + [
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
            'company_id' => $companyId,
        ];
    }

    private function metadata(Request $request, string $type): array
    {
        return match ($type) {
            'mode_de_service' => [
                'operational_mode' => $request->string('operational_mode', 'other')->toString(),
                'requires_delivery_agent' => $request->boolean('requires_delivery_agent'),
                'synced_from_platform' => false,
            ],
            'mode_de_paiement' => [
                'method' => $request->string('method', 'other')->toString(),
                'timing' => $request->string('timing', 'immediate')->toString() === 'deferred' ? 'deferred' : 'immediate',
                'required_fields' => array_values(array_intersect(
                    (array) $request->input('required_fields', []),
                    array_keys(CustomList::PAYMENT_FIELDS)
                )),
            ],
            'taxes' => ['rate' => (float) $request->input('rate', 0)],
            'remises' => [
                'discount_type' => $request->string('discount_type', 'percent')->toString() === 'amount' ? 'amount' : 'percent',
                'value' => (float) $request->input('value', 0),
            ],
            'depenses' => ['expense_kind' => $request->string('expense_kind', 'variable')->toString()],
            'tickets_predefinis' => ['group' => $request->string('group')->toString()],
            default => [],
        };
    }

    private function owns(CustomList $list): void
    {
        abort_unless((int) $list->company_id === (int) Workspace::company()?->id, 404);
    }
}
