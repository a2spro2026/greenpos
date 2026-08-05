<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique(); // openai, azure_openai, claude, gemini, mistral, ollama, local
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('model')->nullable();
            $table->string('base_url')->nullable();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 32)->default('ready');
            $table->text('status_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('persona', 64); // commercial, comptable, stock, pos, direction
            $table->string('icon', 16)->nullable();
            $table->text('system_prompt');
            $table->json('capabilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_prompt_id')->nullable()->constrained('ai_prompts')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('context_module', 64)->nullable();
            $table->string('context_route')->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('status', 32)->default('active'); // active, archived
            $table->unsignedInteger('message_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'status']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 16); // user, assistant, system, tool
            $table->longText('content');
            $table->json('actions')->nullable(); // suggested / pending confirmations
            $table->json('citations')->nullable(); // search results / links
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_id', 'created_at']);
        });

        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 64); // recommendation, insight, alert, tip
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('module', 64)->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->unsignedTinyInteger('priority')->default(50);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_dismissed', 'priority']);
        });

        Schema::create('ai_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->string('action_type', 64);
            $table->string('status', 32)->default('proposed'); // proposed, confirmed, executed, cancelled, failed
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_logs');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_providers');
    }
};
