<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable()->after('slug');
            $table->longText('detailed_description')->nullable()->after('description');
            $table->json('target_audience')->nullable()->after('detailed_description');
            $table->text('ideal_customer_profile')->nullable()->after('target_audience');
            $table->json('problems_solved')->nullable()->after('ideal_customer_profile');
            $table->json('discovery_questions')->nullable()->after('objections');
            $table->json('quotation_requirements')->nullable()->after('discovery_questions');
            $table->json('related_service_ids')->nullable()->after('upsell_ids');
            $table->json('technologies')->nullable()->after('tags');
            $table->string('complexity_level')->nullable()->after('technologies');
            $table->string('typical_timeline')->nullable()->after('complexity_level');
            $table->unsignedInteger('sort_order')->default(0)->after('typical_timeline');

            $table->index(['status', 'sort_order']);
            $table->index('complexity_level');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['status', 'sort_order']);
            $table->dropIndex(['complexity_level']);

            $table->dropColumn([
                'short_description',
                'detailed_description',
                'target_audience',
                'ideal_customer_profile',
                'problems_solved',
                'discovery_questions',
                'quotation_requirements',
                'related_service_ids',
                'technologies',
                'complexity_level',
                'typical_timeline',
                'sort_order',
            ]);
        });
    }
};
