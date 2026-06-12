<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('email_normalized')->nullable()->after('email');
            $table->string('phone_normalized')->nullable()->after('phone');
            $table->string('website')->nullable()->after('phone_normalized');
            $table->string('website_normalized')->nullable()->after('website');

            $table->index('email_normalized');
            $table->index('phone_normalized');
            $table->index('website_normalized');
        });

        Schema::table('lead_scores', function (Blueprint $table) {
            $table->string('temperature')->nullable()->after('score_grade');

            $table->index('temperature');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['email_normalized']);
            $table->dropIndex(['phone_normalized']);
            $table->dropIndex(['website_normalized']);

            $table->dropColumn([
                'email_normalized',
                'phone_normalized',
                'website',
                'website_normalized',
            ]);
        });

        Schema::table('lead_scores', function (Blueprint $table) {
            $table->dropIndex(['temperature']);
            $table->dropColumn('temperature');
        });
    }
};
