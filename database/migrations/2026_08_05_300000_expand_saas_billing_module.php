<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('starts_at');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('saas_subscriptions', 'last_reminder_at')) {
                $table->timestamp('last_reminder_at')->nullable()->after('renews_at');
            }
        });

        Schema::table('saas_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('saas_invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('due_on');
            }
            if (! Schema::hasColumn('saas_invoices', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('line_items');
            }
            if (! Schema::hasColumn('saas_invoices', 'notes')) {
                $table->text('notes')->nullable()->after('pdf_path');
            }
        });

        if (! Schema::hasTable('saas_payment_gateways')) {
            Schema::create('saas_payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique(); // stripe, paypal, cmi, manual
                $table->string('name');
                $table->boolean('is_enabled')->default(false);
                $table->boolean('is_sandbox')->default(true);
                $table->string('mode', 16)->default('test'); // test, live
                $table->json('credentials')->nullable(); // public/secret keys (never expose in UI fully)
                $table->json('settings')->nullable();
                $table->string('webhook_secret')->nullable();
                $table->timestamp('last_tested_at')->nullable();
                $table->string('status', 32)->default('ready'); // ready, connected, error, disabled
                $table->text('status_message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_payment_gateways');

        Schema::table('saas_subscriptions', function (Blueprint $table) {
            foreach (['trial_ends_at', 'converted_at', 'last_reminder_at'] as $col) {
                if (Schema::hasColumn('saas_subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('saas_invoices', function (Blueprint $table) {
            foreach (['paid_at', 'pdf_path', 'notes'] as $col) {
                if (Schema::hasColumn('saas_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
