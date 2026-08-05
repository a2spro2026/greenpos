<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Workspace;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return Workspace::can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return Workspace::can('products.view')
            && $product->company_id === Workspace::company()?->id;
    }

    public function create(User $user): bool
    {
        return Workspace::can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return Workspace::can('products.update')
            && $product->company_id === Workspace::company()?->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return Workspace::can('products.delete')
            && $product->company_id === Workspace::company()?->id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->delete($user, $product);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    public function archive(User $user, Product $product): bool
    {
        return Workspace::can('products.archive')
            && $product->company_id === Workspace::company()?->id;
    }

    public function duplicate(User $user, Product $product): bool
    {
        return Workspace::can('products.duplicate')
            && $product->company_id === Workspace::company()?->id;
    }

    public function export(User $user): bool
    {
        return Workspace::can('products.export');
    }

    public function import(User $user): bool
    {
        return Workspace::can('products.import');
    }
}
