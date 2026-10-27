<?php

use App\Http\Controllers\Messaging\ReminderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'workspace', 'session.active', 'audit'])->prefix('messaging')->name('messaging.')->group(function () {
    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::get('/reminders/create', [ReminderController::class, 'create'])->name('reminders.create');
    Route::post('/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::post('/reminders/process-due', [ReminderController::class, 'processDue'])->name('reminders.process');
    Route::post('/reminders/{reminder}/send', [ReminderController::class, 'send'])->name('reminders.send');
    Route::post('/reminders/{reminder}/mark-sent', [ReminderController::class, 'markSent'])->name('reminders.mark');
    Route::post('/reminders/{reminder}/cancel', [ReminderController::class, 'cancel'])->name('reminders.cancel');
});
