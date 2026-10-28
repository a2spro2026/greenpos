<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rimal POS V2 — phase 2 : commandes clients (additive only).
 * stock_state : null = ancien flux (stock sorti à la confirmation),
 *               'reserved' = « Sortie en cours », 'out' = sorti à la livraison.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'expected_delivery_at')) {
                $table->date('expected_delivery_at')->nullable();
            }
            if (! Schema::hasColumn('sales', 'stock_state')) {
                $table->string('stock_state', 20)->nullable();
            }
            if (! Schema::hasColumn('sales', 'contact_phone')) {
                $table->string('contact_phone', 40)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Additive migration: intentionally no destructive rollback.
    }
};
