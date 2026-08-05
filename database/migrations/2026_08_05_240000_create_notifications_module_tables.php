<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // destinataire; null = broadcast entreprise
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->default('info'); // info, success, warning, error, critical
            $table->string('category', 64)->nullable(); // stock_critical, new_sale, …
            $table->string('priority', 16)->default('normal'); // low, normal, high, critical
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('action_url')->nullable();
            $table->string('status', 16)->default('unread'); // unread, read, archived
            $table->json('channels')->nullable(); // internal, email, sms, whatsapp, push
            $table->json('meta')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'status']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('frequency', 32)->default('realtime'); // realtime, hourly, daily, weekly
            $table->json('types')->nullable(); // enabled types
            $table->json('categories')->nullable(); // enabled categories
            $table->json('channels')->nullable(); // channel toggles
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('app_notifications');
    }
};
