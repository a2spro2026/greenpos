<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rimal V2 — retours fournisseur + avoirs (additif).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_returns')) {
            Schema::create('supplier_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('store_id')->nullable()->index();
                $table->foreignId('supplier_id')->nullable()->index();
                $table->foreignId('purchase_order_id')->nullable()->index();
                $table->foreignId('purchase_receipt_id')->nullable()->index();
                $table->string('number', 40);
                $table->string('status', 20)->default('draft'); // draft | validated | credited | cancelled
                $table->string('reason', 120)->nullable();
                $table->text('notes')->nullable();
                $table->date('returned_at')->nullable();
                $table->decimal('total_ht', 14, 2)->default(0);
                $table->decimal('total_tax', 14, 2)->default(0);
                $table->decimal('total_ttc', 14, 2)->default(0);
                $table->string('credit_note_number', 60)->nullable();
                $table->decimal('credit_note_amount', 14, 2)->nullable();
                $table->date('credited_at')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('supplier_return_lines')) {
            Schema::create('supplier_return_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_return_id')->index();
                $table->foreignId('purchase_receipt_line_id')->nullable();
                $table->foreignId('product_id')->index();
                $table->decimal('quantity', 14, 3)->default(0);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('tax_rate', 6, 2)->default(0);
                $table->decimal('line_total', 14, 2)->default(0);
                $table->timestamps();
            });
        }
        if (Schema::hasTable('pos_sales') && ! Schema::hasColumn('pos_sales', 'archived_at')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // additif : rien n'est supprimé
    }
};
