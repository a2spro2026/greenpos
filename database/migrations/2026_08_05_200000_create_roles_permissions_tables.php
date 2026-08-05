<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // products.view
            $table->string('module', 64); // products
            $table->string('action', 64); // view
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('group', 64)->default('modules'); // modules | special | scope
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index(['group']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 32)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_super')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('users_count_cache')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'is_system']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('role_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->text('message');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_logs');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
