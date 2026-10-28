<?php

use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('shop')->name('shop.')->group(function () {
    Route::get('/{shopSlug}', [ShopController::class, 'home'])->name('home');
    Route::post('/{shopSlug}/cart', [ShopController::class, 'addToCart'])->name('cart.add');
    Route::get('/{shopSlug}/cart', [ShopController::class, 'cart'])->name('cart');
    Route::get('/{shopSlug}/checkout', [ShopController::class, 'checkoutForm'])->name('checkout');
    Route::post('/{shopSlug}/checkout', [ShopController::class, 'checkout'])->name('checkout.store');
    Route::get('/{shopSlug}/thanks/{sale}', [ShopController::class, 'thanks'])->name('thanks');
});
