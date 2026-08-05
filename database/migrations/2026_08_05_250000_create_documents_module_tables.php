<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('module', 64)->nullable(); // products, customers, …
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'module']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('mime', 128)->nullable();
            $table->string('extension', 32)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('category', 64)->nullable();
            $table->string('module', 64)->nullable();
            $table->json('tags')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->string('status', 16)->default('active'); // active, archived
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'folder_id', 'status']);
            $table->index(['company_id', 'module']);
            $table->index(['company_id', 'extension']);
            $table->index(['company_id', 'is_favorite']);
        });

        Schema::create('documentables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->morphs('documentable');
            $table->timestamps();

            $table->unique(['document_id', 'documentable_type', 'documentable_id'], 'documentables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentables');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_folders');
    }
};
