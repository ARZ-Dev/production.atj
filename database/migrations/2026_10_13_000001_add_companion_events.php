<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two event types that run in parallel on the same product — production
     * on a line and packaging on a packaging lane, with the pre-finished good
     * passing straight from one to the other without touching a warehouse.
     *
     *  - event_types.companion_event_type_id: the "executed with another event
     *    type" setting. Creating an event of this type also creates one of the
     *    companion type.
     *  - events.linked_event_id: set on the companion event, pointing at the
     *    event it runs with (its lead). The pair starts and ends together.
     */
    public function up(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->foreignId('companion_event_type_id')->nullable()->after('start_items_unverifiable')
                ->constrained('event_types')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('linked_event_id')->nullable()->after('to_plan_id')
                ->constrained('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_event_id');
        });

        Schema::table('event_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('companion_event_type_id');
        });
    }
};
