<?php

use App\Console\Commands\ProcessSaasBillingCommand;
use App\Console\Commands\ProcessSystemBackupsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ProcessSaasBillingCommand::class)->dailyAt('02:15');
Schedule::command('greenpos:system-backups --health')->dailyAt('03:00');
Schedule::command(ProcessSystemBackupsCommand::class)->hourly();

Artisan::command('reminders:send', function () {
    $n = app(\App\Services\ReminderService::class)->processDue(200);
    $this->info("Reminders sent/processed: {$n}");
})->purpose('Send due messaging reminders');

Schedule::command('reminders:send')->everyFiveMinutes();

Schedule::command('tasks:run-automation')->hourly();

Artisan::command('pos:archive-held', function () {
    $n = 0;
    foreach (\App\Models\Company::query()->get() as $company) {
        $n += app(\App\Services\HeldTicketArchiver::class)->archiveStale($company);
    }
    $this->info("Tickets en attente archivés : {$n}");
})->purpose('Archive (sans supprimer) les tickets en attente trop anciens');

Schedule::command('pos:archive-held')->dailyAt('04:10');
