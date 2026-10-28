<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 32);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('job_role', 32)->default('other'); // admin|manager|cashier|vendor|other
            $table->string('status', 32)->default('active'); // active|inactive|suspended
            $table->date('hire_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('bank_iban', 64)->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('commission_percent', 8, 2)->default(0);
            $table->decimal('commission_fixed', 14, 2)->default(0);
            $table->string('pay_type', 32)->default('monthly'); // hourly|daily|weekly|monthly
            $table->decimal('base_rate', 14, 2)->default(0);
            $table->decimal('overtime_multiplier', 8, 2)->default(1.5);
            $table->decimal('normal_hours_per_day', 8, 2)->default(8);
            $table->string('rest_day', 32)->nullable();
            $table->decimal('absence_penalty', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'job_role']);
            $table->index(['company_id', 'store_id']);
            $table->index(['company_id', 'user_id']);
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->decimal('worked_hours', 8, 2)->nullable();
            $table->string('status', 32)->default('present'); // present|absent|leave|late|partial
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'store_id']);
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->string('pay_type', 32)->default('monthly');
            $table->decimal('base_rate', 14, 2)->default(0);
            $table->decimal('normal_hours', 10, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->unsignedInteger('worked_days')->default(0);
            $table->unsignedInteger('absent_days')->default(0);
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('overtime_amount', 14, 2)->default(0);
            $table->decimal('absence_deduction', 14, 2)->default(0);
            $table->decimal('prime', 14, 2)->default(0);
            $table->decimal('bonus', 14, 2)->default(0);
            $table->decimal('advance', 14, 2)->default(0);
            $table->decimal('retention', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->string('status', 32)->default('pending'); // pending|paid|partial
            $table->string('payment_method', 32)->nullable(); // cash|transfer|check|null
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'year', 'month']);
            $table->index(['company_id', 'year', 'month']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
    }
};
