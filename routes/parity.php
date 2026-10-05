<?php

use App\Http\Controllers\Crm\AutomationRuleController;
use App\Http\Controllers\Crm\CrmIncidentController;
use App\Http\Controllers\CustomListController;
use App\Http\Controllers\DeliveryPlatformController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosTicketController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/settings/listes', [CustomListController::class, 'index'])->name('settings.lists.index');
Route::get('/settings/listes/create', [CustomListController::class, 'create'])->name('settings.lists.create');
Route::post('/settings/listes', [CustomListController::class, 'store'])->name('settings.lists.store');
Route::get('/settings/listes/{custom_list}/edit', [CustomListController::class, 'edit'])->name('settings.lists.edit');
Route::put('/settings/listes/{custom_list}', [CustomListController::class, 'update'])->name('settings.lists.update');
Route::delete('/settings/listes/{custom_list}', [CustomListController::class, 'destroy'])->name('settings.lists.destroy');

Route::get('/settings/plateformes', [DeliveryPlatformController::class, 'index'])->name('settings.delivery-platforms.index');
Route::post('/settings/plateformes', [DeliveryPlatformController::class, 'store'])->name('settings.delivery-platforms.store');
Route::put('/settings/plateformes/{platform}', [DeliveryPlatformController::class, 'update'])->name('settings.delivery-platforms.update');

Route::get('/pos/imprimantes', [PrinterController::class, 'index'])->name('pos.printers.index');
Route::get('/pos/imprimantes/create', [PrinterController::class, 'create'])->name('pos.printers.create');
Route::post('/pos/imprimantes', [PrinterController::class, 'store'])->name('pos.printers.store');
Route::get('/pos/imprimantes/{printer}/edit', [PrinterController::class, 'edit'])->name('pos.printers.edit');
Route::put('/pos/imprimantes/{printer}', [PrinterController::class, 'update'])->name('pos.printers.update');

Route::get('/pos/offline/snapshot', [PosController::class, 'offlineSnapshot'])->name('pos.offline.snapshot');
Route::post('/pos/offline/sync', [PosController::class, 'offlineSync'])->name('pos.offline.sync');
Route::get('/pos/tickets/{sale}/kitchen', [PosTicketController::class, 'kitchen'])->name('pos.tickets.kitchen');
Route::get('/pos/tickets/{sale}/refund', [PosTicketController::class, 'refundForm'])->name('pos.tickets.refund');
Route::post('/pos/tickets/{sale}/refund', [PosTicketController::class, 'refund'])->name('pos.tickets.refund.store');
Route::post('/pos/payments/{payment}/collect', [PosTicketController::class, 'collect'])->name('pos.payments.collect');
Route::post('/pos/payments/{payment}/reschedule', [PosTicketController::class, 'reschedule'])->name('pos.payments.reschedule');
Route::post('/pos/payments/{payment}/cancel', [PosTicketController::class, 'cancelPayment'])->name('pos.payments.cancel');

Route::get('/sales/{sale}/refund', [SaleController::class, 'refundForm'])->name('sales.refund');
Route::post('/sales/{sale}/refund', [SaleController::class, 'refund'])->name('sales.refund.store');
Route::post('/sales/payments/{payment}/collect', [SaleController::class, 'collectPayment'])->name('sales.payments.collect');
Route::post('/sales/payments/{payment}/reschedule', [SaleController::class, 'reschedulePayment'])->name('sales.payments.reschedule');
Route::post('/sales/payments/{payment}/cancel', [SaleController::class, 'cancelPayment'])->name('sales.payments.cancel');

Route::get('/crm/incidents', [CrmIncidentController::class, 'index'])->name('crm.incidents.index');
Route::get('/crm/incidents/create', [CrmIncidentController::class, 'create'])->name('crm.incidents.create');
Route::post('/crm/incidents', [CrmIncidentController::class, 'store'])->name('crm.incidents.store');
Route::post('/crm/incidents/assignments', [CrmIncidentController::class, 'assignType'])->name('crm.incidents.assign');
Route::get('/crm/incidents/{incident}', [CrmIncidentController::class, 'show'])->name('crm.incidents.show');
Route::put('/crm/incidents/{incident}', [CrmIncidentController::class, 'update'])->name('crm.incidents.update');

Route::get('/crm/automations', [AutomationRuleController::class, 'index'])->name('crm.automations.index');
Route::post('/crm/automations', [AutomationRuleController::class, 'store'])->name('crm.automations.store');
Route::post('/crm/automations/run', [AutomationRuleController::class, 'run'])->name('crm.automations.run');
Route::put('/crm/automations/{rule}', [AutomationRuleController::class, 'update'])->name('crm.automations.update');
