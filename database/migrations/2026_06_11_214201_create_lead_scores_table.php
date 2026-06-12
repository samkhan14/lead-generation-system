<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->string('score_grade')->nullable();
            $table->json('factors')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['lead_id', 'calculated_at']);
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_scores');
    }
};
