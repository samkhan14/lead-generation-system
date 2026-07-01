<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_calls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('voice_provider_id')->constrained('voice_providers')->cascadeOnDelete();
            $table->foreignId('ai_employee_id')->nullable()->constrained('ai_employees')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_call_id')->nullable()->index();
            $table->string('direction')->default('outbound')->index();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('status')->default('queued')->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('cost_usd', 12, 6)->nullable();
            $table->longText('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->string('recording_url')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index(['ai_employee_id', 'created_at']);
            $table->index(['voice_provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_calls');
    }
};
