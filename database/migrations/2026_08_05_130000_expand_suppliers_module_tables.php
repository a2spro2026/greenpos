<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('code')->nullable()->after('company_id');
            $table->string('company_name')->nullable()->after('name');
            $table->string('category')->nullable()->after('company_name');
            $table->string('status', 32)->default('active')->after('category');
            $table->string('mobile')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable()->default('Maroc');
            $table->string('postal_code', 32)->nullable();
            $table->string('currency', 8)->nullable()->default('MAD');
            $table->string('payment_terms')->nullable();
            $table->unsignedInteger('delivery_delay_days')->nullable();
            $table->string('tax_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'city']);
        });

        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category')->nullable(); // contract, certificate, other
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_change_logs');
        Schema::dropIfExists('supplier_documents');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'code', 'company_name', 'category', 'status', 'mobile', 'website',
                'address', 'city', 'region', 'country', 'postal_code', 'currency',
                'payment_terms', 'delivery_delay_days', 'tax_id', 'notes',
                'created_by', 'updated_by',
            ]);
        });
    }
};
