<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('keyword');
            $table->string('industry')->nullable();
            $table->string('country');
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('status')->default('pending'); // pending|running|completed|failed
            $table->unsignedSmallInteger('max_results')->default(20);
            $table->string('scraper_used')->nullable(); // playwright|places_api
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_found')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_jobs');
    }
};
