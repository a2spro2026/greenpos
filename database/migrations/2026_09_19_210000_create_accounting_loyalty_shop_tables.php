<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_accounts')) {
            Schema::create('account_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('code', 32);
                $table->string('name');
                $table->string('type', 32);
                $table->foreignId('parent_id')->nullable()->constrained('account_accounts')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('account_entries')) {
            Schema::create('account_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                $table->date('entry_date');
                $table->string('number')->nullable();
                $table->string('label');
                $table->string('source_type', 64)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['company_id', 'entry_date']);
            });
        }

        if (! Schema::hasTable('account_entry_lines')) {
            Schema::create('account_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_entry_id')->constrained('account_entries')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('account_accounts')->cascadeOnDelete();
                $table->decimal('debit', 14, 2)->default(0);
                $table->decimal('credit', 14, 2)->default(0);
                $table->string('label')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pos_tables')) {
            Schema::create('pos_tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('group_name')->nullable();
                $table->unsignedSmallInteger('seats')->default(4);
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('status', 32)->default('free');
                $table->foreignId('current_pos_sale_id')->nullable()->constrained('pos_sales')->nullOnDelete();
                $table->timestamps();
                $table->index(['company_id', 'store_id', 'status']);
            });
        }

        if (! Schema::hasTable('api_tokens')) {
            Schema::create('api_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('token_hash', 64)->unique();
                $table->string('token_prefix', 12);
                $table->json('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('sale_payments')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('sale_payments', 'status')) {
                    $table->string('status', 32)->default('collected')->after('method');
                }
                if (! Schema::hasColumn('sale_payments', 'is_deferred')) {
                    $table->boolean('is_deferred')->default(false)->after('status');
                }
                if (! Schema::hasColumn('sale_payments', 'due_at')) {
                    $table->timestamp('due_at')->nullable()->after('is_deferred');
                }
                if (! Schema::hasColumn('sale_payments', 'collected_at')) {
                    $table->timestamp('collected_at')->nullable()->after('due_at');
                }
                if (! Schema::hasColumn('sale_payments', 'collection_notes')) {
                    $table->text('collection_notes')->nullable()->after('collected_at');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'loyalty_points')) {
                    $table->unsignedInteger('loyalty_points')->default(0)->after('balance');
                }
                if (! Schema::hasColumn('customers', 'loyalty_tier')) {
                    $table->string('loyalty_tier', 32)->nullable()->after('loyalty_points');
                }
            });
        }

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'shop_slug')) {
                    $table->string('shop_slug')->nullable()->unique()->after('name');
                }
                if (! Schema::hasColumn('companies', 'shop_enabled')) {
                    $table->boolean('shop_enabled')->default(false)->after('shop_slug');
                }
            });
        }
    }

    public function down(): void
    {
        // non-destructive down for partial applies
    }
};
