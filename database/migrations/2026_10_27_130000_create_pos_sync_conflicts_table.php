<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rimal V2 — phase 5 : journal des conflits de synchronisation hors ligne (additif).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_sync_conflicts')) {
            return;
        }
        Schema::create('pos_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index();
            $table->foreignId('store_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->uuid('client_uuid');
            $table->json('payload');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('open'); // open | resolved | synced
            $table->unsignedInteger('attempts')->default(1);
            $table->foreignId('pos_sale_id')->nullable();
            $table->foreignId('resolved_by')->nullable();
            $table->timestamp('offline_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'client_uuid']);
        });
    }

    public function down(): void
    {
        // Additif : aucune suppression de données.
    }
};
