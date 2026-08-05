<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_plans', 'api_enabled')) {
                $table->boolean('api_enabled')->default(false)->after('storage_gb');
            }
            if (! Schema::hasColumn('saas_plans', 'support_included')) {
                $table->boolean('support_included')->default(true)->after('api_enabled');
            }
            if (! Schema::hasColumn('saas_plans', 'support_level')) {
                $table->string('support_level', 32)->default('email')->after('support_included');
            }
            if (! Schema::hasColumn('saas_plans', 'backups_enabled')) {
                $table->boolean('backups_enabled')->default(false)->after('support_level');
            }
            if (! Schema::hasColumn('saas_plans', 'custom_domain_enabled')) {
                $table->boolean('custom_domain_enabled')->default(false)->after('backups_enabled');
            }
            if (! Schema::hasColumn('saas_plans', 'trial_days')) {
                $table->unsignedSmallInteger('trial_days')->default(14)->after('custom_domain_enabled');
            }
        });

        Schema::table('saas_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_subscriptions', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'suspend_reason')) {
                $table->string('suspend_reason')->nullable()->after('suspended_at');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('suspend_reason');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'notes')) {
                $table->text('notes')->nullable()->after('cancel_reason');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'renewal_count')) {
                $table->unsignedInteger('renewal_count')->default(0)->after('notes');
            }
        });

        Schema::create('saas_subscription_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_subscription_id')->nullable()->constrained('saas_subscriptions')->nullOnDelete();
            $table->foreignId('saas_tenant_id')->nullable()->constrained('saas_tenants')->nullOnDelete();
            $table->string('type', 64); // expiring_soon, payment_failed, renewed, limit_exceeded
            $table->string('severity', 16)->default('info'); // info, warning, critical
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_read', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // Rename Business → Standard if present
        if (Schema::hasTable('saas_plans')) {
            DB::table('saas_plans')->where('code', 'business')->update([
                'code' => 'standard',
                'name' => 'Standard',
                'tagline' => 'Pour les commerces en croissance',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_subscription_alerts');

        Schema::table('saas_subscriptions', function (Blueprint $table) {
            foreach (['suspended_at', 'suspend_reason', 'cancel_reason', 'notes', 'renewal_count'] as $col) {
                if (Schema::hasColumn('saas_subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('saas_plans', function (Blueprint $table) {
            foreach (['api_enabled', 'support_included', 'support_level', 'backups_enabled', 'custom_domain_enabled', 'trial_days'] as $col) {
                if (Schema::hasColumn('saas_plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
