<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module', 64)->nullable()->index();
            $table->string('action', 64)->index(); // login, create, update, delete, export, …
            $table->string('event_type', 64)->nullable()->index(); // auth, crud, finance, stock, settings, …
            $table->string('severity', 16)->default('info')->index(); // info, warning, important, critical
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('result', 32)->default('success'); // success, failure, denied
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device', 128)->nullable();
            $table->string('browser', 128)->nullable();
            $table->string('platform', 128)->nullable();
            $table->string('route_name')->nullable();
            $table->string('http_method', 16)->nullable();
            $table->string('url', 512)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('system_notes')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['company_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
