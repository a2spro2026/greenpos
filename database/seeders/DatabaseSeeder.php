<?php

namespace Database\Seeders;

use App\Services\PlatformBootstrapService;
use Illuminate\Database\Seeder;

/**
 * Seed minimal plateforme uniquement (pas de données métier / démo).
 * Pour un jeu de démo optionnel, créer un seeder dédié et l’appeler explicitement.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlatformSeeder::class);
    }
}
