<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_pause_activities', function (Blueprint $table) {
            // Why this emergency event happened (required at creation time).
            $table->text('reason')->nullable()->after('event_type_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
