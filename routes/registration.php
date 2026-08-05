<?php

use App\Http\Controllers\CompanyRegistration\PublicRegistrationController;
use App\Http\Controllers\CompanyRegistration\RegistrationTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/register-company', [PublicRegistrationController::class, 'create'])
    ->name('register-company');
Route::post('/register-company', [PublicRegistrationController::class, 'store'])
    ->name('register-company.store');
Route::get('/register-company/success', [PublicRegistrationController::class, 'success'])
    ->name('register-company.success');

Route::get('/suivi-demande', [RegistrationTrackingController::class, 'form'])
    ->name('register-company.track');
Route::post('/suivi-demande', [RegistrationTrackingController::class, 'lookup'])
    ->name('register-company.track.lookup');
Route::get('/suivi-demande/{reference}', [RegistrationTrackingController::class, 'show'])
    ->where('reference', '[A-Za-z0-9\-]+')
    ->name('register-company.track.show');
