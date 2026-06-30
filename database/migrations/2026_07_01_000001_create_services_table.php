<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->json('benefits')->nullable();
            $table->json('deliverables')->nullable();
            $table->text('pricing_notes')->nullable();
            $table->json('faqs')->nullable();
            $table->json('objections')->nullable();
            $table->json('cross_sell_ids')->nullable();
            $table->json('upsell_ids')->nullable();
            $table->json('tags')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
