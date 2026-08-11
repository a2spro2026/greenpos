<?php

namespace App\Console\Commands;

use App\Services\PlatformBootstrapService;
use App\Services\PlatformResetService;
use Illuminate\Console\Command;

class ResetPlatformDemoCommand extends Command
{
    protected $signature = 'greenpos:reset-demo {--force : Skip confirmation}';

    protected $description = 'Supprime toutes les données de démonstration. Conserve Super Admin, plans et paramètres plateforme.';

    public function handle(PlatformResetService $reset): int
    {
        if (! $this->option('force') && ! $this->confirm('Supprimer toutes les données métier / démo ?', true)) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        $result = $reset->reset();

        $this->info('Réinitialisation terminée.');
        $this->line('  Tables vidées : '.$result['deleted_tables']);
        $this->line('  Entreprises   : '.$result['companies_left']);
        $this->line('  Utilisateurs  : '.$result['users_left']);
        $this->line('  Super Admin   : '.PlatformBootstrapService::SUPER_ADMIN_USERNAME);
        $this->line('  Mot de passe  : '.PlatformBootstrapService::SUPER_ADMIN_PASSWORD);

        return self::SUCCESS;
    }
}
