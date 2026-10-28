<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Executed with another event type" is retired: a continuous line is now
     * one run event across its stations, so production no longer drags
     * packaging companions along. Planned-only companions left on a board are
     * plain events after this; there is nothing else to migrate.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planned_packaging_id');
            $table->dropConstrainedForeignId('linked_event_id');
        });

        Schema::table('event_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('companion_event_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->foreignId('companion_event_type_id')->nullable()->after('start_items_unverifiable')
                ->constrained('event_types')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('linked_event_id')->nullable()->after('to_plan_id')
                ->constrained('events')->nullOnDelete();
            $table->foreignId('planned_packaging_id')->nullable()->after('linked_event_id')
                ->constrained('packagings')->nullOnDelete();
        });
    }
};
