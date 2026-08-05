<?php

use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GreenPOS Official Marketing Site (public, no ERP / no Super Admin)
|--------------------------------------------------------------------------
*/

Route::name('site.')->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/fonctionnalites', [SiteController::class, 'features'])->name('features');
    Route::get('/tarifs', [SiteController::class, 'pricing'])->name('pricing');
    Route::get('/secteurs', [SiteController::class, 'sectors'])->name('sectors');
    Route::get('/a-propos', [SiteController::class, 'about'])->name('about');
    Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
    Route::post('/contact', [SiteController::class, 'contactSubmit'])->name('contact.submit');

    // Friendly aliases — reuse existing ERP-independent auth & registration
    Route::redirect('/creer-mon-entreprise', '/register-company')->name('register');
    Route::redirect('/connexion', '/login')->name('login');
});
