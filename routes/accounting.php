<?php

use App\Http\Controllers\Accounting\CashJournalController;
use App\Http\Controllers\Accounting\CollectionController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\LedgerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'workspace', 'session.active', 'audit'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Accounting\AccountingHomeController::class, 'index'])->name('home');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/cash', [CashJournalController::class, 'index'])->name('cash.index');
    Route::get('/cash/create', [CashJournalController::class, 'create'])->name('cash.create');
    Route::post('/cash', [CashJournalController::class, 'store'])->name('cash.store');
    Route::post('/cash/rebuild', [CashJournalController::class, 'rebuild'])->name('cash.rebuild');

    Route::get('/accounts', [LedgerController::class, 'accounts'])->name('accounts.index');
    Route::post('/accounts', [LedgerController::class, 'storeAccount'])->name('accounts.store');
    Route::get('/ledger', [LedgerController::class, 'entries'])->name('ledger.index');
    Route::post('/ledger', [LedgerController::class, 'storeEntry'])->name('ledger.store');
    Route::get('/bilan', [LedgerController::class, 'bilan'])->name('bilan');
    Route::get('/vat', [LedgerController::class, 'vat'])->name('vat');

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::post('/collections/{payment}/collect', [CollectionController::class, 'collect'])->name('collections.collect');
    Route::post('/collections/{payment}/reschedule', [CollectionController::class, 'reschedule'])->name('collections.reschedule');
    Route::post('/collections/{payment}/cancel', [CollectionController::class, 'cancel'])->name('collections.cancel');
});
