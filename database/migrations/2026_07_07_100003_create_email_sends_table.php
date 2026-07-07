<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sends', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->foreignId('email_provider_id')->nullable()->constrained('email_providers')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject');
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->string('status')->default('pending')->index();
            $table->boolean('is_test')->default(false);
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['email_campaign_id', 'status']);
            $table->index(['to_email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sends');
    }
};
