<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_scores', function (Blueprint $table) {
            $table->unsignedTinyInteger('intent_score')->default(0)->after('score');
            $table->unsignedTinyInteger('opportunity_score')->default(0)->after('intent_score');
            $table->unsignedTinyInteger('authenticity_score')->default(0)->after('opportunity_score');
            $table->string('scoring_version')->default('v2')->after('authenticity_score');

            $table->index('intent_score');
            $table->index('opportunity_score');
            $table->index('authenticity_score');
        });
    }

    public function down(): void
    {
        Schema::table('lead_scores', function (Blueprint $table) {
            $table->dropIndex(['intent_score']);
            $table->dropIndex(['opportunity_score']);
            $table->dropIndex(['authenticity_score']);

            $table->dropColumn([
                'intent_score',
                'opportunity_score',
                'authenticity_score',
                'scoring_version',
            ]);
        });
    }
};
