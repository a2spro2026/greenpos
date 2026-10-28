<?php

use App\Models\Product;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->prefix('v1')->group(function () {
    Route::get('/products', function (Request $request) {
        $companyId = (int) $request->attributes->get('api_company_id');

        return Product::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'sku', 'barcode', 'sale_price', 'tax_rate', 'track_stock', 'status']);
    });

    Route::get('/stock', function (Request $request) {
        $companyId = (int) $request->attributes->get('api_company_id');

        return StockLevel::query()
            ->where('company_id', $companyId)
            ->with('product:id,name,sku')
            ->limit(500)
            ->get();
    });
});
