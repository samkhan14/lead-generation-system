<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->json('capabilities')->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->decimal('input_price_per_1k', 10, 6)->nullable();
            $table->decimal('output_price_per_1k', 10, 6)->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['ai_provider_id', 'slug']);
            $table->index(['ai_provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
