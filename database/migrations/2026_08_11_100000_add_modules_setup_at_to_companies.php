<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies') || Schema::hasColumn('companies', 'modules_setup_at')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('modules_setup_at')->nullable()->after('status');
        });

        // Entreprises déjà en production : ne pas forcer le parcours de 1re configuration.
        DB::table('companies')->whereNull('modules_setup_at')->update([
            'modules_setup_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'modules_setup_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('modules_setup_at');
            });
        }
    }
};
