<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_platform_admin')) {
                $table->boolean('is_platform_admin')->default(false)->after('email');
            }
        });

        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_yearly', 12, 2)->default(0);
            $table->string('currency', 3)->default('MAD');
            $table->unsignedInteger('max_users')->default(5);
            $table->unsignedInteger('max_stores')->default(1);
            $table->unsignedInteger('storage_gb')->default(5);
            $table->json('modules')->nullable(); // authorized modules
            $table->json('features')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('stripe_price_monthly')->nullable();
            $table->string('stripe_price_yearly')->nullable();
            $table->timestamps();
        });

        Schema::create('saas_tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('status', 32)->default('trial'); // trial, active, suspended, cancelled
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspend_reason')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('saas_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_tenant_id')->constrained('saas_tenants')->cascadeOnDelete();
            $table->foreignId('saas_plan_id')->constrained('saas_plans')->restrictOnDelete();
            $table->string('status', 32)->default('trialing'); // trialing, active, past_due, cancelled, expired
            $table->string('billing_cycle', 16)->default('monthly'); // monthly, yearly
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('MAD');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('provider', 32)->nullable(); // stripe, paypal, cmi, manual
            $table->string('provider_subscription_id')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['saas_tenant_id', 'status']);
            $table->index(['ends_at', 'status']);
        });

        Schema::create('saas_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_tenant_id')->constrained('saas_tenants')->cascadeOnDelete();
            $table->foreignId('saas_subscription_id')->nullable()->constrained('saas_subscriptions')->nullOnDelete();
            $table->string('license_key')->unique();
            $table->string('status', 32)->default('active'); // active, revoked, expired
            $table->unsignedInteger('max_activations')->default(1);
            $table->unsignedInteger('activations_count')->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('saas_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_tenant_id')->constrained('saas_tenants')->cascadeOnDelete();
            $table->foreignId('saas_subscription_id')->nullable()->constrained('saas_subscriptions')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('provider', 32)->default('manual'); // stripe, paypal, cmi, manual
            $table->string('provider_payment_id')->nullable();
            $table->string('status', 32)->default('pending'); // pending, paid, failed, refunded
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('MAD');
            $table->string('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['saas_tenant_id', 'status']);
            $table->index(['paid_at']);
        });

        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_tenant_id')->constrained('saas_tenants')->cascadeOnDelete();
            $table->foreignId('saas_payment_id')->nullable()->constrained('saas_payments')->nullOnDelete();
            $table->foreignId('saas_subscription_id')->nullable()->constrained('saas_subscriptions')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('status', 32)->default('draft'); // draft, issued, paid, void
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('MAD');
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
        });

        Schema::create('saas_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_tenant_id')->constrained('saas_tenants')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->string('status', 32)->default('pending'); // pending, active, failed
            $table->boolean('ssl_enabled')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('saas_platform_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('captured_at');
            $table->decimal('cpu_percent', 5, 2)->nullable();
            $table->decimal('memory_percent', 5, 2)->nullable();
            $table->decimal('disk_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('storage_used_bytes')->nullable();
            $table->json('services')->nullable(); // health of queue, cache, db, mail…
            $table->string('overall_status', 32)->default('healthy');
            $table->timestamps();

            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_platform_snapshots');
        Schema::dropIfExists('saas_domains');
        Schema::dropIfExists('saas_invoices');
        Schema::dropIfExists('saas_payments');
        Schema::dropIfExists('saas_licenses');
        Schema::dropIfExists('saas_subscriptions');
        Schema::dropIfExists('saas_tenants');
        Schema::dropIfExists('saas_plans');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_platform_admin')) {
                $table->dropColumn('is_platform_admin');
            }
        });
    }
};
