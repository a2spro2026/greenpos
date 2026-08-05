<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->nullable()->index();
            $table->string('type', 32)->default('lead'); // prospect, lead
            $table->string('status', 32)->default('new'); // new, contacted, qualified, unqualified, converted, archived
            $table->string('source', 64)->nullable(); // website, referral, cold_call, email, event, partner, other
            $table->string('rating', 16)->nullable(); // hot, warm, cold
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 64)->nullable();
            $table->string('mobile', 64)->nullable();
            $table->string('job_title')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('website')->nullable();
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->string('currency', 3)->default('MAD');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'type']);
            $table->index(['company_id', 'owner_user_id']);
        });

        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('number')->nullable()->index();
            $table->string('name');
            $table->string('stage', 32)->default('new'); // new, contacted, qualified, proposal, negotiation, won, lost
            $table->unsignedTinyInteger('probability')->default(10);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('MAD');
            $table->date('expected_close_on')->nullable();
            $table->date('closed_on')->nullable();
            $table->string('lost_reason')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('pipeline_order')->default(0);
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'stage']);
            $table->index(['company_id', 'owner_user_id', 'stage']);
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('crm_opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32); // call, email, meeting, task, follow_up, note
            $table->string('status', 32)->default('planned'); // planned, done, cancelled
            $table->string('subject');
            $table->text('body')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('priority', 16)->default('normal'); // low, normal, high
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'starts_at']);
            $table->index(['company_id', 'due_at', 'status']);
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::create('crm_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 64)->nullable();
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->string('category', 64)->nullable(); // intro, follow_up, proposal, closing
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('crm_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_email_template_id')->nullable()->constrained('crm_email_templates')->nullOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('crm_opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('status', 32)->default('draft'); // draft, sent, opened, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->string('tracking_token', 64)->nullable()->unique();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('crm_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period', 16)->default('month'); // month, quarter, year
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->decimal('target_amount', 14, 2)->default(0);
            $table->unsignedInteger('target_deals')->default(0);
            $table->decimal('achieved_amount', 14, 2)->default(0);
            $table->unsignedInteger('achieved_deals')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'owner_user_id', 'period', 'year', 'month'], 'crm_goals_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_goals');
        Schema::dropIfExists('crm_email_logs');
        Schema::dropIfExists('crm_email_templates');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_opportunities');
        Schema::dropIfExists('crm_leads');
    }
};
