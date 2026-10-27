<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('category', 64)->nullable();
            $table->string('expense_type', 16)->default('variable'); // fixed|variable
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('MAD');
            $table->date('expense_date');
            $table->string('payment_method', 32)->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence', 32)->nullable(); // monthly|quarterly|semiannual|annual
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'expense_date']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'expense_type']);
        });

        Schema::create('cash_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->string('direction', 8); // in|out
            $table->string('source_type', 32); // sale|expense|collection|manual|payroll|other
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('label');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 32)->nullable();
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
            $table->index(['company_id', 'direction']);
            $table->index(['company_id', 'source_type', 'source_id']);
            $table->index(['store_id', 'entry_date']);
        });

        Schema::create('product_bom_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // finished
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'component_product_id'], 'product_bom_unique');
            $table->index(['company_id', 'product_id']);
        });

        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number');
            $table->string('status', 16)->default('draft'); // draft|validated
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->date('produced_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'produced_at']);
        });

        Schema::create('production_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // component
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['production_id']);
        });

        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 32); // production|loss|adjustment
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('consumed_at');
            $table->timestamps();

            $table->index(['company_id', 'consumed_at']);
            $table->index(['company_id', 'reason']);
        });

        Schema::create('stock_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('responsible_name')->nullable();
            $table->string('number');
            $table->string('loss_type', 32); // loss|breakage|expiration|theft
            $table->date('loss_date');
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'loss_date']);
            $table->index(['company_id', 'loss_type']);
        });

        Schema::create('stock_loss_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_loss_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stock_loss_id']);
        });

        Schema::create('delivery_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('name');
            $table->string('type', 16)->default('internal'); // internal|external
            $table->string('phone', 40)->nullable();
            $table->string('email', 160)->nullable();
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('commission_fixed', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('platform_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);
        });

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (! Schema::hasColumn('sales', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('salesperson_id');
                }
                if (! Schema::hasColumn('sales', 'delivery_agent_id')) {
                    $table->unsignedBigInteger('delivery_agent_id')->nullable()->after('employee_id');
                }
                if (! Schema::hasColumn('sales', 'service_mode')) {
                    $table->string('service_mode', 32)->nullable()->after('origin');
                }
                if (! Schema::hasColumn('sales', 'delivery_address')) {
                    $table->text('delivery_address')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('sales', 'ticket_name')) {
                    $table->string('ticket_name')->nullable()->after('delivery_address');
                }
                if (! Schema::hasColumn('sales', 'delivery_commission')) {
                    $table->decimal('delivery_commission', 14, 2)->nullable()->after('ticket_name');
                }
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_sales', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('cashier_id');
                }
                if (! Schema::hasColumn('pos_sales', 'delivery_agent_id')) {
                    $table->unsignedBigInteger('delivery_agent_id')->nullable()->after('employee_id');
                }
                if (! Schema::hasColumn('pos_sales', 'service_mode')) {
                    $table->string('service_mode', 32)->nullable()->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                foreach (['employee_id', 'delivery_agent_id', 'service_mode'] as $col) {
                    if (Schema::hasColumn('pos_sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                foreach (['employee_id', 'delivery_agent_id', 'service_mode', 'delivery_address', 'ticket_name', 'delivery_commission'] as $col) {
                    if (Schema::hasColumn('sales', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('delivery_agents');
        Schema::dropIfExists('stock_loss_lines');
        Schema::dropIfExists('stock_losses');
        Schema::dropIfExists('material_consumptions');
        Schema::dropIfExists('production_consumptions');
        Schema::dropIfExists('productions');
        Schema::dropIfExists('product_bom_components');
        Schema::dropIfExists('cash_journal_entries');
        Schema::dropIfExists('expenses');
    }
};
