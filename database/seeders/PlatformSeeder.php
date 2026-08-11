<?php

namespace Database\Seeders;

use App\Services\PlatformBootstrapService;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $result = app(PlatformBootstrapService::class)->ensureMinimal();

        $this->command?->info('Platform bootstrap OK (sans données démo)');
        $this->command?->info('  Super Admin : '.PlatformBootstrapService::SUPER_ADMIN_USERNAME);
        $this->command?->info('  Password    : '.PlatformBootstrapService::SUPER_ADMIN_PASSWORD);
        $this->command?->info('  User ID     : '.$result['super_admin']->id);
    }
}
