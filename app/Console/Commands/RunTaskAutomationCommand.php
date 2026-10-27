<?php

namespace App\Console\Commands;

use App\Services\TaskAutomationService;
use Illuminate\Console\Command;

class RunTaskAutomationCommand extends Command
{
    protected $signature = 'tasks:run-automation {--company= : Limiter à une entreprise}';

    protected $description = 'Règles opérationnelles : stock faible → tâche, récurrence et rappels agenda';

    public function handle(TaskAutomationService $automation): int
    {
        if ($this->option('company')) {
            $result = $automation->runCompany((int) $this->option('company'));
            $this->info('Tâches '.$result['tasks'].' · rappels '.$result['reminders'].' · récurrences '.$result['recurrences']);

            return self::SUCCESS;
        }

        $totals = $automation->runAll();
        $this->info('Entreprises '.$totals['companies'].' · tâches '.$totals['tasks'].' · rappels '.$totals['reminders'].' · récurrences '.$totals['recurrences']);

        return self::SUCCESS;
    }
}
