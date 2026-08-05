<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('saas_tenant_id')->nullable()->constrained('saas_tenants')->nullOnDelete();
            $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
            $table->string('status')->default('registered'); // registered|provisioned|wizard|completed
            $table->unsignedTinyInteger('wizard_step')->default(1);
            $table->json('draft')->nullable();
            $table->json('checklist')->nullable();
            $table->boolean('welcome_shown')->default(false);
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_onboardings');
    }
};
