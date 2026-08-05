<?php

namespace App\Console\Commands;

use App\Services\SaasBillingService;
use App\Services\SaasSubscriptionService;
use Illuminate\Console\Command;

class ProcessSaasBillingCommand extends Command
{
    protected $signature = 'saas:process-billing
                            {--no-convert : Ne pas convertir automatiquement les essais}
                            {--days=14 : Fenêtre de rappels (jours)}';

    protected $description = 'Rappels, conversion d’essais et renouvellements automatiques SaaS';

    public function handle(SaasBillingService $billing, SaasSubscriptionService $subscriptions): int
    {
        $days = (int) $this->option('days');
        $this->info('Scan expirations…');
        $expiring = $subscriptions->scanExpiring($days);

        $this->info('Rappels de renouvellement…');
        $reminders = $billing->sendRenewalReminders($days);

        $this->info('Conversion / expiration des essais…');
        $trials = $billing->processExpiredTrials(! $this->option('no-convert'));

        $this->info('Renouvellements automatiques…');
        $renewals = $billing->processAutoRenewals();

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Alertes expiration', $expiring],
                ['Rappels envoyés', $reminders],
                ['Essais convertis', $trials['converted']],
                ['Essais expirés', $trials['expired']],
                ['Renouvelés', $renewals['renewed']],
                ['Échecs paiement', $renewals['failed']],
            ]
        );

        return self::SUCCESS;
    }
}
