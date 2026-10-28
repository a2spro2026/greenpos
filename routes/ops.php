<?php

use App\Http\Controllers\DeliveryAgentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\StockLossController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'workspace', 'session.active', 'audit'])->prefix('ops')->name('ops.')->group(function () {
    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::get('/production/create', [ProductionController::class, 'create'])->name('production.create');
    Route::post('/production', [ProductionController::class, 'store'])->name('production.store');
    Route::get('/production/bom', [ProductionController::class, 'bomForm'])->name('production.bom');
    Route::post('/production/bom', [ProductionController::class, 'bomStore'])->name('production.bom.store');
    Route::get('/production/{production}', [ProductionController::class, 'show'])->name('production.show');
    Route::get('/production/{production}/edit', [ProductionController::class, 'edit'])->name('production.edit');
    Route::put('/production/{production}', [ProductionController::class, 'update'])->name('production.update');
    Route::post('/production/{production}/validate', [ProductionController::class, 'validateProduction'])->name('production.validate');

    Route::get('/losses', [StockLossController::class, 'index'])->name('losses.index');
    Route::get('/losses/create', [StockLossController::class, 'create'])->name('losses.create');
    Route::post('/losses', [StockLossController::class, 'store'])->name('losses.store');
    Route::get('/losses/{loss}', [StockLossController::class, 'show'])->name('losses.show');
});

Route::middleware(['web', 'auth', 'workspace', 'session.active', 'audit'])->prefix('delivery')->name('delivery.')->group(function () {
    Route::get('/', [DeliveryAgentController::class, 'index'])->name('index');
    Route::get('/ranking', [DeliveryAgentController::class, 'ranking'])->name('ranking');
    Route::get('/create', [DeliveryAgentController::class, 'create'])->name('create');
    Route::post('/', [DeliveryAgentController::class, 'store'])->name('store');
    Route::get('/{delivery}', [DeliveryAgentController::class, 'show'])->name('show');
    Route::get('/{delivery}/edit', [DeliveryAgentController::class, 'edit'])->name('edit');
    Route::put('/{delivery}', [DeliveryAgentController::class, 'update'])->name('update');
    Route::delete('/{delivery}', [DeliveryAgentController::class, 'destroy'])->name('destroy');
});
