<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scrape_jobs', function (Blueprint $table) {
            $table->boolean('is_watch')->default(false)->after('source_channel')->index();
            $table->unsignedSmallInteger('watch_interval_hours')->nullable()->after('is_watch');
            $table->timestamp('last_dispatched_at')->nullable()->after('watch_interval_hours');
        });
    }

    public function down(): void
    {
        Schema::table('scrape_jobs', function (Blueprint $table) {
            $table->dropColumn(['is_watch', 'watch_interval_hours', 'last_dispatched_at']);
        });
    }
};
