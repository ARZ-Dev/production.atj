<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lead event can now run with several companions (e.g. one production
     * run packed on two packaging lanes), its output split between them by
     * each packaging's capacity. The split is worked out in the add-event
     * popup, before anything is on the board, so a companion records the
     * packaging it was planned on there.
     *
     * When the lead is dropped on the board, a companion that isn't placed yet
     * lands on its planned packaging at the lead's time. Dragging it elsewhere
     * updates the plan; unplacing it on its own clears it.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('planned_packaging_id')->nullable()->after('linked_event_id')
                ->constrained('packagings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planned_packaging_id');
        });
    }
};
