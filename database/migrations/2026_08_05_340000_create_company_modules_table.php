<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('module_key', 64);
            $table->boolean('is_enabled')->default(false);
            $table->string('source')->default('plan'); // plan|manual
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'module_key']);
            $table->index(['company_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_modules');
    }
};
