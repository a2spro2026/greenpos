<?php

use App\Http\Controllers\PosCashController;
use Illuminate\Support\Facades\Route;

// Rimal POS V2 — caisse, journal et règlements à encaisser.
Route::get('/pos/journal', [PosCashController::class, 'journal'])->name('pos.journal');
Route::get('/pos/journal/print', [PosCashController::class, 'print'])->name('pos.journal.print');
Route::get('/pos/journal/export', [PosCashController::class, 'export'])->name('pos.journal.export');
Route::post('/pos/cash-movements', [PosCashController::class, 'storeMovement'])->name('pos.cash-movements.store');
Route::get('/pos/a-encaisser', [PosCashController::class, 'collections'])->name('pos.collections');
