<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false)->after('status');
            }
            if (! Schema::hasColumn('products', 'is_on_sale')) {
                $table->boolean('is_on_sale')->default(false)->after('is_favorite');
            }
            if (! Schema::hasColumn('products', 'price_type')) {
                $table->string('price_type', 16)->default('fixed')->after('is_on_sale'); // fixed|variable
            }
            if (! Schema::hasColumn('products', 'is_taxable')) {
                $table->boolean('is_taxable')->default(true)->after('price_type');
            }
            if (! Schema::hasColumn('products', 'color_tag')) {
                $table->string('color_tag', 32)->nullable()->after('is_taxable');
            }
        });

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (! Schema::hasColumn('sales', 'delivery_commission')) {
                    $table->decimal('delivery_commission', 14, 2)->nullable()->after('delivery_agent_id');
                }
            });
        }

        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('pos_sales', 'delivery_commission')) {
                    $table->decimal('delivery_commission', 14, 2)->nullable()->after('delivery_agent_id');
                }
                if (! Schema::hasColumn('pos_sales', 'ticket_name')) {
                    $table->string('ticket_name')->nullable()->after('service_mode');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['is_favorite', 'is_on_sale', 'price_type', 'is_taxable', 'color_tag'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'delivery_commission')) {
            Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('delivery_commission'));
        }
        if (Schema::hasTable('pos_sales')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                if (Schema::hasColumn('pos_sales', 'delivery_commission')) {
                    $table->dropColumn('delivery_commission');
                }
                if (Schema::hasColumn('pos_sales', 'ticket_name')) {
                    $table->dropColumn('ticket_name');
                }
            });
        }
    }
};
