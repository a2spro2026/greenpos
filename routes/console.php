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