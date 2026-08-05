<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('phone')->nullable()->after('username');
            $table->string('photo_path')->nullable()->after('phone');
            $table->string('job_title')->nullable()->after('photo_path');
            $table->string('department')->nullable()->after('job_title');
            $table->date('hired_at')->nullable()->after('department');
            $table->string('status', 32)->default('active')->after('hired_at');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->string('last_login_device')->nullable()->after('last_login_ip');
            $table->timestamp('invited_at')->nullable()->after('last_login_device');
            $table->timestamp('deactivated_at')->nullable()->after('invited_at');
            $table->softDeletes();
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->string('status', 32)->default('active')->after('role');
            $table->boolean('is_primary')->default(false)->after('status');
        });

        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->text('message');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'created_at']);
        });

        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
        });

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category', 64)->default('other');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('role', 32)->default('sales');
            $table->string('token', 64)->unique();
            $table->string('status', 32)->default('pending'); // pending, accepted, expired, cancelled
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
        Schema::dropIfExists('user_documents');
        Schema::dropIfExists('user_login_logs');
        Schema::dropIfExists('user_logs');

        Schema::table('company_user', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_primary']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'first_name', 'last_name', 'username', 'phone', 'photo_path',
                'job_title', 'department', 'hired_at', 'status',
                'last_login_at', 'last_login_ip', 'last_login_device',
                'invited_at', 'deactivated_at',
            ]);
        });
    }
};
