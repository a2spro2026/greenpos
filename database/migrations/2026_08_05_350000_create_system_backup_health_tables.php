<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('type', 20)->default('manual'); // manual|auto
            $table->string('schedule', 20)->nullable(); // daily|weekly|monthly
            $table->string('status', 20)->default('pending'); // pending|running|success|failed
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('include_files')->default(true);
            $table->json('manifest')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40); // disk_low|backup_failed|service_down|database_down
            $table->string('severity', 20)->default('warning'); // info|warning|critical
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_resolved']);
            $table->index(['type', 'severity']);
        });

        Schema::create('system_health_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 32); // backup|restore|error|incident|health
            $table->string('severity', 20)->default('info');
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'category']);
            $table->index(['created_at']);
        });

        Schema::create('system_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('overall', 20)->default('healthy'); // healthy|degraded|critical
            $table->string('database_status', 20)->default('ok');
            $table->unsignedInteger('response_ms')->default(0);
            $table->decimal('disk_used_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('disk_free_bytes')->default(0);
            $table->json('services')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_snapshots');
        Schema::dropIfExists('system_health_events');
        Schema::dropIfExists('system_alerts');
        Schema::dropIfExists('system_backups');
    }
};
