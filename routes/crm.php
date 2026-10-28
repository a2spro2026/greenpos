<?php

use App\Http\Controllers\Crm\CrmController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'workspace', 'session.active', 'audit'])->prefix('crm')->name('crm.')->group(function () {
    Route::get('/', [CrmController::class, 'dashboard'])->name('dashboard');
    Route::get('/pipeline', [CrmController::class, 'pipeline'])->name('pipeline');
    Route::post('/opportunities/{opportunity}/move', [CrmController::class, 'move'])->name('opportunities.move');

    Route::get('/leads', [CrmController::class, 'leadsIndex'])->name('leads.index');
    Route::get('/leads/create', [CrmController::class, 'leadsCreate'])->name('leads.create');
    Route::post('/leads', [CrmController::class, 'leadsStore'])->name('leads.store');
    Route::get('/leads/{lead}', [CrmController::class, 'leadsShow'])->name('leads.show');
    Route::get('/leads/{lead}/edit', [CrmController::class, 'leadsEdit'])->name('leads.edit');
    Route::put('/leads/{lead}', [CrmController::class, 'leadsUpdate'])->name('leads.update');
    Route::post('/leads/{lead}/qualify', [CrmController::class, 'leadsQualify'])->name('leads.qualify');
    Route::post('/leads/{lead}/assign', [CrmController::class, 'leadsAssign'])->name('leads.assign');
    Route::post('/leads/{lead}/convert', [CrmController::class, 'leadsConvert'])->name('leads.convert');
    Route::post('/leads/{lead}/archive', [CrmController::class, 'leadsArchive'])->name('leads.archive');

    Route::get('/opportunities', [CrmController::class, 'opportunitiesIndex'])->name('opportunities.index');
    Route::get('/opportunities/create', [CrmController::class, 'opportunitiesCreate'])->name('opportunities.create');
    Route::post('/opportunities', [CrmController::class, 'opportunitiesStore'])->name('opportunities.store');
    Route::get('/opportunities/{opportunity}', [CrmController::class, 'opportunitiesShow'])->name('opportunities.show');

    Route::get('/activities', [CrmController::class, 'activitiesIndex'])->name('activities.index');
    Route::get('/activities/create', [CrmController::class, 'activitiesCreate'])->name('activities.create');
    Route::post('/activities', [CrmController::class, 'activitiesStore'])->name('activities.store');
    Route::get('/activities/{activity}', [CrmController::class, 'activitiesShow'])->name('activities.show');
    Route::post('/activities/{activity}/complete', [CrmController::class, 'activitiesComplete'])->name('activities.complete');
    Route::post('/activities/{activity}/reminder', [CrmController::class, 'activitiesReminder'])->name('activities.reminder');
    Route::post('/activities/{activity}/attachments', [CrmController::class, 'activitiesAttachment'])->name('activities.attachment');

    Route::get('/calendar', [CrmController::class, 'calendar'])->name('calendar');
    Route::get('/emails', [CrmController::class, 'emailsIndex'])->name('emails.index');
    Route::post('/emails', [CrmController::class, 'emailsStore'])->name('emails.store');
    Route::get('/reports', [CrmController::class, 'reports'])->name('reports');
    Route::post('/ai', [CrmController::class, 'aiAssist'])->name('ai');

    Route::get('/automations', [\App\Http\Controllers\Crm\AutomationController::class, 'index'])->name('automations.index');
    Route::get('/automations/create', [\App\Http\Controllers\Crm\AutomationController::class, 'create'])->name('automations.create');
    Route::post('/automations', [\App\Http\Controllers\Crm\AutomationController::class, 'store'])->name('automations.store');
    Route::post('/automations/{automation}/run', [\App\Http\Controllers\Crm\AutomationController::class, 'run'])->name('automations.run');
    Route::delete('/automations/{automation}', [\App\Http\Controllers\Crm\AutomationController::class, 'destroy'])->name('automations.destroy');

    Route::get('/incidents', [\App\Http\Controllers\Crm\IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/create', [\App\Http\Controllers\Crm\IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidents', [\App\Http\Controllers\Crm\IncidentController::class, 'store'])->name('incidents.store');
    Route::post('/incidents/{incident}/status', [\App\Http\Controllers\Crm\IncidentController::class, 'updateStatus'])->name('incidents.status');
});

Route::get('/crm/email/track/{token}.gif', [CrmController::class, 'emailTrack'])->name('crm.emails.track');
