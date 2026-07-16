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
        Schema::table('event_status_logs', function (Blueprint $table) {
            // User-entered time of the status change (defaults to now in the
            // UI); created_at stays the insert time.
            $table->dateTime('happened_at')->nullable()->after('to_status');
        });

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->dateTime('happened_at')->nullable()->after('expected_duration');
            // Emergency events can be closed with a time + note.
            $table->dateTime('ended_at')->nullable()->after('happened_at');
            $table->text('end_note')->nullable()->after('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->dropColumn('happened_at');
        });

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->dropColumn(['happened_at', 'ended_at', 'end_note']);
        });
    }
};
