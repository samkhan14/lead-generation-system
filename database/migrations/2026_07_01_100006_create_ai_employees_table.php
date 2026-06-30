<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('role')->index();
            $table->string('department')->nullable();
            $table->text('description')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->longText('behavior_prompt')->nullable();
            $table->json('knowledge_sources')->nullable();
            $table->json('allowed_actions')->nullable();
            $table->json('allowed_tools')->nullable();
            $table->boolean('memory_enabled')->default(true);
            $table->unsignedInteger('context_window')->default(8192);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->foreignId('fallback_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('fallback_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('voice_id')->nullable();
            $table->string('language', 10)->default('en');
            $table->json('working_hours')->nullable();
            $table->string('status')->default('training')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_employees');
    }
};
