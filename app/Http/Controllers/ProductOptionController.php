<?php

namespace App\Http\Controllers;

use App\Models\ProductOption;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductOptionController extends Controller
{
    public function index(): View
    {
        $this->authorize('products.options');
        $options = ProductOption::query()
            ->forCompany(Workspace::company()->id)
            ->with('variants')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('products.options.index', compact('options'));
    }

    public function create(): View
    {
        $this->authorize('products.options');

        return view('products.options.form', ['option' => new ProductOption(['selection_mode' => 'multiple', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('products.options');
        $data = $this->validated($request);
        $option = ProductOption::query()->create([
            'company_id' => Workspace::company()->id,
            'name' => $data['name'],
            'selection_mode' => $data['selection_mode'],
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        $this->syncVariants($option, $data['variants'] ?? []);

        return redirect()->route('products.options.index')->with('success', 'Option enregistrée.');
    }

    public function edit(ProductOption $option): View
    {
        $this->authorize('products.options');
        $this->owns($option);
        $option->load('variants');

        return view('products.options.form', compact('option'));
    }

    public function update(Request $request, ProductOption $option): RedirectResponse
    {
        $this->authorize('products.options');
        $this->owns($option);
        $data = $this->validated($request);
        $option->update([
            'name' => $data['name'],
            'selection_mode' => $data['selection_mode'],
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        $this->syncVariants($option, $data['variants'] ?? []);

        return redirect()->route('products.options.index')->with('success', 'Option mise à jour.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'selection_mode' => ['required', 'in:fixed,multiple'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.extra_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function syncVariants(ProductOption $option, array $rows): void
    {
        $keep = [];
        $i = 0;
        foreach ($rows as $row) {
            if (! filled($row['name'] ?? null)) {
                continue;
            }
            $payload = [
                'name' => $row['name'],
                'extra_price' => $row['extra_price'] ?? 0,
                'is_active' => true,
                'sort_order' => $i++,
            ];
            if (! empty($row['id'])) {
                $variant = $option->variants()->whereKey($row['id'])->first();
                if ($variant) {
                    $variant->update($payload);
                    $keep[] = $variant->id;
                    continue;
                }
            }
            $keep[] = $option->variants()->create($payload)->id;
        }
        $option->variants()->whereNotIn('id', $keep)->update(['is_active' => false]);
    }

    private function owns(ProductOption $option): void
    {
        abort_unless((int) $option->company_id === (int) Workspace::company()?->id, 404);
    }
}
