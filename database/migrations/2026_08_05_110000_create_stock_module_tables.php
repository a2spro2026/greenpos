<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('min_quantity', 14, 3)->default(0);
            $table->decimal('max_quantity', 14, 3)->nullable();
            $table->decimal('reserved_quantity', 14, 3)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'store_id']);
            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'quantity']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_id')->nullable()->index();
            $table->string('type', 32); // in, out, adjustment, transfer
            $table->decimal('quantity', 14, 3);
            $table->decimal('quantity_before', 14, 3)->default(0);
            $table->decimal('quantity_after', 14, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reference')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('related_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamp('moved_at');
            $table->timestamps();

            $table->index(['company_id', 'moved_at']);
            $table->index(['company_id', 'type']);
            $table->index(['store_id', 'product_id']);
        });

        Schema::create('stock_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('status', 32)->default('draft'); // draft, in_progress, validated, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('stock_inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('stock_inventories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_qty', 14, 3)->default(0);
            $table->decimal('counted_qty', 14, 3)->nullable();
            $table->decimal('difference', 14, 3)->nullable();
            $table->boolean('is_counted')->default(false);
            $table->timestamps();

            $table->unique(['inventory_id', 'product_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('inventory_id')->references('id')->on('stock_inventories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
        });
        Schema::dropIfExists('stock_inventory_lines');
        Schema::dropIfExists('stock_inventories');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
    }
};
