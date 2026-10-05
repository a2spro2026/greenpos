<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('products.subcategories');
        $company = Workspace::company();

        return view('products.subcategories.index', [
            'subcategories' => Subcategory::query()->forCompany($company->id)->with('category')->orderBy('name')->get(),
            'categories' => Category::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('products.subcategories');
        $companyId = Workspace::company()->id;
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        $category = Category::query()->where('company_id', $companyId)->whereKey($data['category_id'])->firstOrFail();
        Subcategory::query()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'name' => $data['name'],
            'slug' => $this->slug($companyId, $data['name']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Sous-catégorie enregistrée.');
    }

    public function update(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('products.subcategories');
        abort_unless((int) $subcategory->company_id === (int) Workspace::company()?->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $subcategory->update([
            'name' => $data['name'],
            'slug' => $this->slug($subcategory->company_id, $data['name'], $subcategory->id),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Sous-catégorie mise à jour.');
    }

    private function slug(int $companyId, string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'sous-categorie';
        $slug = $base;
        $i = 2;
        while (Subcategory::query()->forCompany($companyId)->where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
