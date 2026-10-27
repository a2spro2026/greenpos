<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rimal POS V2 — phase 1 (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_sales', 'ticket_discount_percent')) {
                $table->decimal('ticket_discount_percent', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('pos_sales', 'delivery_platform')) {
                $table->string('delivery_platform', 120)->nullable();
            }
            if (! Schema::hasColumn('pos_sales', 'platform_commission')) {
                $table->decimal('platform_commission', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('pos_sales', 'pos_table_id')) {
                $table->unsignedBigInteger('pos_table_id')->nullable()->index();
            }
        });

        Schema::table('pos_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_payments', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (! Schema::hasColumn('pos_payments', 'collected_at')) {
                $table->timestamp('collected_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Additive migration: intentionally no destructive rollback.
    }
};
