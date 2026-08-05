<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_tenants', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('city');
            }
            if (! Schema::hasColumn('saas_tenants', 'primary_domain')) {
                $table->string('primary_domain')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('saas_tenants', 'storage_used_mb')) {
                $table->unsignedInteger('storage_used_mb')->default(0)->after('primary_domain');
            }
            if (! Schema::hasColumn('saas_tenants', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('suspend_reason');
            }
        });

        Schema::create('saas_audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->index(); // login, error, incident, billing, tenant, system
            $table->string('severity', 16)->default('info'); // info, warning, critical
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('saas_tenant_id')->nullable()->constrained('saas_tenants')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['occurred_at', 'category']);
        });

        if (Schema::hasTable('saas_platform_snapshots') && ! Schema::hasColumn('saas_platform_snapshots', 'meta')) {
            Schema::table('saas_platform_snapshots', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('overall_status');
            });
        }

        // Align plan naming: Standard → Business (display) for Enterprise catalog
        if (Schema::hasTable('saas_plans')) {
            DB::table('saas_plans')->where('code', 'standard')->update([
                'name' => 'Business',
                'tagline' => 'Pour les commerces en croissance',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_audit_events');

        Schema::table('saas_tenants', function (Blueprint $table) {
            foreach (['logo_path', 'primary_domain', 'storage_used_mb', 'archived_at'] as $col) {
                if (Schema::hasColumn('saas_tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
