<?php

use App\Http\Controllers\Onboarding\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', [OnboardingController::class, 'landing'])->name('onboarding.landing');
Route::get('/essayer', fn () => redirect()->route('onboarding.landing'))->name('onboarding.try');

Route::middleware('guest')->group(function () {
    Route::get('/register', [OnboardingController::class, 'showRegister'])->name('onboarding.register');
    Route::post('/register', [OnboardingController::class, 'register'])->name('onboarding.register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/register/plan', [OnboardingController::class, 'showPlan'])->name('onboarding.plan');
    Route::post('/register/plan', [OnboardingController::class, 'selectPlan'])->name('onboarding.plan.store');
});

Route::middleware(['workspace', 'audit'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/wizard', [OnboardingController::class, 'wizard'])->name('wizard');
    Route::post('/wizard', [OnboardingController::class, 'saveWizard'])->name('wizard.store');
    Route::post('/wizard/skip', [OnboardingController::class, 'skipWizard'])->name('wizard.skip');
    Route::post('/welcome/dismiss', [OnboardingController::class, 'dismissWelcome'])->name('welcome.dismiss');
});
