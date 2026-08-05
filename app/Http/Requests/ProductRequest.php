<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product
            ? Workspace::can('products.update')
            : Workspace::can('products.create');
    }

    public function rules(): array
    {
        $companyId = Workspace::company()?->id;
        $productId = $this->route('product')?->id;

        return [
            'type' => ['required', Rule::in(array_keys(Product::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'barcode')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNotNull('barcode'))
                    ->ignore($productId),
            ],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'brand_id' => [
                'nullable',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'unit' => ['required', Rule::in(array_keys(Product::UNITS))],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_starts_at' => ['nullable', 'date'],
            'discount_ends_at' => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
            'status' => ['required', Rule::in(array_keys(Product::STATUSES))],
            'track_stock' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => [
                Rule::exists('stores', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:64'],
            'variants.*.barcode' => ['nullable', 'string', 'max:64'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.size' => ['nullable', 'string', 'max:64'],
            'variants.*.color' => ['nullable', 'string', 'max:64'],
            'variants.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'sale_price.required' => 'Le prix de vente est obligatoire.',
            'sku.unique' => 'Ce SKU existe déjà dans l’entreprise.',
            'barcode.unique' => 'Ce code-barres existe déjà dans l’entreprise.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'track_stock' => $this->boolean('track_stock'),
            'purchase_price' => $this->input('purchase_price', 0),
        ]);
    }
}
