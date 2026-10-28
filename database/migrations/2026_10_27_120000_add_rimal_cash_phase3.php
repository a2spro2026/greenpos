<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rimal POS V2 — phase 3 : caisse & journal (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_cash_movements')) {
            Schema::create('pos_cash_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->unsignedBigInteger('pos_session_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('direction', 8); // in | out
                $table->decimal('amount', 14, 2);
                $table->string('reason', 200);
                $table->string('client_uuid', 64)->nullable()->index();
                $table->timestamps();
            });
        }

        Schema::table('pos_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_sessions', 'opening_breakdown')) {
                $table->json('opening_breakdown')->nullable();
            }
            if (! Schema::hasColumn('pos_sessions', 'closing_breakdown')) {
                $table->json('closing_breakdown')->nullable();
            }
            if (! Schema::hasColumn('pos_sessions', 'cash_in_total')) {
                $table->decimal('cash_in_total', 14, 2)->default(0);
            }
            if (! Schema::hasColumn('pos_sessions', 'cash_out_total')) {
                $table->decimal('cash_out_total', 14, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Additive migration: intentionally no destructive rollback.
    }
};
