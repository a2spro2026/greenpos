<?php

use App\Http\Controllers\PosCashController;
use Illuminate\Support\Facades\Route;

// Rimal POS V2 — caisse, journal et règlements à encaisser.
Route::get('/pos/journal', [PosCashController::class, 'journal'])->name('pos.journal');
Route::get('/pos/journal/print', [PosCashController::class, 'print'])->name('pos.journal.print');
Route::get('/pos/journal/export', [PosCashController::class, 'export'])->name('pos.journal.export');
Route::post('/pos/cash-movements', [PosCashController::class, 'storeMovement'])->name('pos.cash-movements.store');
Route::get('/pos/a-encaisser', [PosCashController::class, 'collections'])->name('pos.collections');

// Rimal V2 — phase 4 : pilotage, rapports complémentaires, BL client, documents centraux
Route::middleware(['auth'])->group(function () {
    Route::get('/reports/pilotage', [\App\Http\Controllers\RimalReportController::class, 'pilotage'])->name('reports.pilotage');
    Route::get('/reports/pilotage/export', [\App\Http\Controllers\RimalReportController::class, 'exportPilotage'])->name('reports.pilotage.export');
    Route::get('/reports/documents', [\App\Http\Controllers\RimalReportController::class, 'documents'])->name('reports.documents');
    Route::get('/reports/rimal/{type}', [\App\Http\Controllers\RimalReportController::class, 'show'])->whereIn('type', array_keys(\App\Services\RimalReportService::REPORTS))->name('reports.extra');
    Route::get('/reports/rimal/{type}/export', [\App\Http\Controllers\RimalReportController::class, 'export'])->whereIn('type', array_keys(\App\Services\RimalReportService::REPORTS))->name('reports.extra.export');
    Route::get('/reports/rimal/{type}/print', [\App\Http\Controllers\RimalReportController::class, 'print'])->whereIn('type', array_merge(['pilotage'], array_keys(\App\Services\RimalReportService::REPORTS)))->name('reports.extra.print');
    Route::get('/sales/{sale}/bon-livraison', [\App\Http\Controllers\RimalReportController::class, 'deliveryNote'])->name('sales.delivery-note');
});

// Rimal V2 — phase 5 : conflits de synchronisation hors ligne
Route::get('/pos/sync-conflicts', [\App\Http\Controllers\PosSyncConflictController::class, 'index'])->name('pos.sync-conflicts.index');
Route::post('/pos/sync-conflicts/{conflict}/retry', [\App\Http\Controllers\PosSyncConflictController::class, 'retry'])->name('pos.sync-conflicts.retry');
Route::post('/pos/sync-conflicts/{conflict}/resolve', [\App\Http\Controllers\PosSyncConflictController::class, 'resolve'])->name('pos.sync-conflicts.resolve');
