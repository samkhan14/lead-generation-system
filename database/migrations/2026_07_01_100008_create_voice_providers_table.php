<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('api_key')->nullable();
            $table->string('api_base_url')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->unsignedSmallInteger('priority')->default(100);
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->unsignedTinyInteger('retry_count')->default(2);
            $table->string('status')->default('disabled')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_providers');
    }
};
