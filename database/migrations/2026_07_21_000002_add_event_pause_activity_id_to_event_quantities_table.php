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
        Schema::table('event_quantities', function (Blueprint $table) {
            // Set for source = "emergency": the items consumed while carrying
            // out an emergency event (cleaning, maintenance, …).
            $table->foreignId('event_pause_activity_id')
                ->nullable()
                ->after('event_status_log_id')
                ->constrained('event_pause_activities')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_quantities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_pause_activity_id');
        });
    }
};
