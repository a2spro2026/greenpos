<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Gestion des catégories produits (ajout / renommage / ordre). Pas de suppression. */
class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);
        $companyId = Workspace::company()->id;
        $categories = Category::query()->where('company_id', $companyId)->orderBy('sort_order')->orderBy('name')->get();
        $counts = Product::query()->where('company_id', $companyId)->selectRaw('category_id, COUNT(*) as n')->groupBy('category_id')->pluck('n', 'category_id');

        return view('products.categories.index', compact('categories', 'counts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $companyId = Workspace::company()->id;
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'sort_order' => ['nullable', 'integer', 'min:0']]);
        Category::query()->create(['company_id' => $companyId, 'name' => $data['name'], 'slug' => $this->slug($companyId, $data['name']), 'sort_order' => $data['sort_order'] ?? 0]);

        return back()->with('success', 'Catégorie ajoutée.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('create', Product::class);
        abort_unless($category->company_id === Workspace::company()->id, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'sort_order' => ['nullable', 'integer', 'min:0']]);
        $category->update(['name' => $data['name'], 'sort_order' => $data['sort_order'] ?? $category->sort_order]);

        return back()->with('success', 'Catégorie mise à jour.');
    }

    protected function slug(int $companyId, string $name): string
    {
        $base = Str::slug($name) ?: 'categorie';
        $slug = $base; $i = 2;
        while (Category::query()->where('company_id', $companyId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
