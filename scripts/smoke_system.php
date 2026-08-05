<?php

/**
 * Smoke — Sauvegardes & Santé du système.
 * Usage: php scripts/smoke_system.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\SystemAlert;
use App\Models\SystemBackup;
use App\Models\SystemHealthEvent;
use App\Models\User;
use App\Services\BackupService;
use App\Services\SystemHealthService;
use App\Support\ModuleCatalog;
use App\Support\Workspace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$ok = 0;
$fail = 0;

function assertTrue(bool $cond, string $msg): void
{
    global $ok, $fail;
    if ($cond) {
        echo "  OK  {$msg}\n";
        $ok++;
    } else {
        echo " FAIL {$msg}\n";
        $fail++;
    }
}

echo "=== System backup & health smoke ===\n";

assertTrue(Schema::hasTable('system_backups'), 'system_backups table');
assertTrue(Schema::hasTable('system_alerts'), 'system_alerts table');
assertTrue(Schema::hasTable('system_health_events'), 'system_health_events table');
assertTrue(Schema::hasTable('system_health_snapshots'), 'system_health_snapshots table');
assertTrue(isset(ModuleCatalog::all()['system']), 'module catalog system');
assertTrue(Route::has('system.dashboard'), 'route system.dashboard');
assertTrue(Route::has('system.backups'), 'route system.backups');
assertTrue(Route::has('system.backups.restore.run'), 'route restore');
assertTrue(Route::has('system.alerts'), 'route alerts');
assertTrue(Route::has('system.journal'), 'route journal');

$user = User::query()->where('email', 'admin@greenpos.test')->first() ?? User::query()->first();
assertTrue((bool) $user, 'demo user');
Auth::login($user);

$company = $user->companies()->first() ?? Company::query()->first();
assertTrue((bool) $company, 'company');
Workspace::set($company, $company->stores()->first());

$backupSvc = app(BackupService::class);
$healthSvc = app(SystemHealthService::class);

$policy = $backupSvc->savePolicy([
    'auto_backup' => true,
    'frequency' => 'daily',
    'retention_days' => 14,
    'include_files' => true,
    'note' => 'Smoke policy',
], $company);
assertTrue(($policy['frequency'] ?? '') === 'daily', 'policy daily');
assertTrue(($policy['auto_backup'] ?? false) === true, 'policy auto on');

$row = CompanySetting::query()->where('company_id', $company->id)->where('group', 'backup')->first();
assertTrue((bool) $row, 'backup settings persisted');

$health = $healthSvc->check($company, true);
assertTrue(($health['database_status'] ?? '') === 'ok', 'database ok');
assertTrue(isset($health['disk']['used_percent']), 'disk stats');
assertTrue(isset($health['services']['storage']), 'services map');
assertTrue(in_array($health['overall'] ?? '', ['healthy', 'degraded', 'critical'], true), 'overall status');

$backup = $backupSvc->createManual($company, $user, true);
assertTrue($backup->status === 'success', 'manual backup success');
assertTrue((int) $backup->size_bytes > 0, 'backup size > 0');
assertTrue((int) $backup->duration_ms >= 0, 'backup duration');
assertTrue((bool) $backup->path && File::exists(storage_path('app/'.$backup->path)), 'zip exists');

$preview = $backupSvc->preview($backup);
assertTrue(! empty($preview['manifest']), 'preview manifest');
assertTrue($preview['exists'] === true, 'preview archive exists');

$beforeName = $company->fresh()->name;
$marker = 'SMOKE-RESTORE-'.$company->id;
$company->update(['name' => $marker]);
assertTrue($company->fresh()->name === $marker, 'name changed before restore');

$backupSvc->restore($backup, $user, 'RESTAURER');
$company->refresh();
assertTrue($company->name === $beforeName || $company->name !== $marker, 'restore applied company data');

$events = SystemHealthEvent::query()->where('company_id', $company->id)->whereIn('category', ['backup', 'restore'])->count();
assertTrue($events >= 2, 'journal has backup/restore events');

SystemAlert::query()->create([
    'company_id' => $company->id,
    'type' => 'disk_low',
    'severity' => 'warning',
    'title' => 'Smoke disk alert',
    'body' => 'test',
]);
$open = SystemAlert::query()->where('company_id', $company->id)->open()->count();
assertTrue($open >= 1, 'open alert created');

$exit = Artisan::call('greenpos:system-backups', ['--health' => true]);
assertTrue($exit === 0, 'artisan greenpos:system-backups');

$other = Company::query()->where('id', '!=', $company->id)->first();
if ($other) {
    $foreign = SystemBackup::query()->forCompany($company->id)->count();
    $otherCount = SystemBackup::query()->forCompany($other->id)->where('code', $backup->code)->count();
    assertTrue($foreign >= 1, 'company A has backup');
    assertTrue($otherCount === 0, 'company B isolated from backup code');
} else {
    assertTrue(true, 'skip isolation (single company)');
}

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);
