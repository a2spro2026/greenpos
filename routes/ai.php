<?php

use App\Http\Controllers\Ai\AiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'workspace', 'audit'])->prefix('ai')->name('ai.')->group(function () {
    Route::get('/', [AiController::class, 'dashboard'])->name('dashboard');
    Route::get('/providers', [AiController::class, 'providers'])->name('providers');
    Route::put('/providers/{provider}', [AiController::class, 'updateProvider'])->name('providers.update');
    Route::get('/conversations/{conversation}', [AiController::class, 'conversation'])->name('conversations.show');
    Route::get('/context', [AiController::class, 'context'])->name('context');
    Route::post('/chat', [AiController::class, 'chat'])->name('chat');
    Route::post('/actions/{action}/confirm', [AiController::class, 'confirmAction'])->name('actions.confirm');
    Route::post('/actions/{action}/cancel', [AiController::class, 'cancelAction'])->name('actions.cancel');
    Route::post('/suggestions/{suggestion}/dismiss', [AiController::class, 'dismissSuggestion'])->name('suggestions.dismiss');
});
