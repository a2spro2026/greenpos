<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Services\SystemHealthService;
use Illuminate\Console\Command;

class ProcessSystemBackupsCommand extends Command
{
    protected $signature = 'greenpos:system-backups {--health : Run health checks for all companies}';

    protected $description = 'Exécute les sauvegardes automatiques dues et optionnellement les contrôles de santé';

    public function handle(BackupService $backups, SystemHealthService $health): int
    {
        $count = $backups->runDueScheduled();
        $this->info("Sauvegardes automatiques exécutées : {$count}");

        if ($this->option('health')) {
            $health->check(null, true);
            $this->info('Contrôle de santé plateforme enregistré.');
        }

        return self::SUCCESS;
    }
}
