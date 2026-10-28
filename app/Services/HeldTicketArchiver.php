<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PosSale;
use App\Support\SettingsDefaults;
use Illuminate\Support\Facades\Schema;

// Archive (flag archived_at) les tickets en attente plus vieux que N jours — aucune suppression.
class HeldTicketArchiver
{
    public function archiveStale(Company $company): int
    {
        if (! Schema::hasColumn('pos_sales', 'archived_at')) {
            return 0;
        }
        $settings = app(SettingService::class)->getGroup('pos', $company);
        $days = (int) ($settings['held_archive_days'] ?? SettingsDefaults::for('pos')['held_archive_days'] ?? 30);
        if ($days <= 0) {
            return 0;
        }

        return PosSale::query()
            ->where('company_id', $company->id)
            ->where('status', 'held')
            ->whereNull('archived_at')
            ->where(fn ($q) => $q->where('held_at', '<', now()->subDays($days))
                ->orWhere(fn ($q2) => $q2->whereNull('held_at')->where('created_at', '<', now()->subDays($days))))
            ->update(['archived_at' => now()]);
    }
}
