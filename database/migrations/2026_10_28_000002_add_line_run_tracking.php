<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a line run records while it runs and when it ends.
     *
     *  - events.feeds_event_id: on a preparation event (a mix), the line run it
     *    makes dough for — set by "Plan mixes".
     *  - events.needs_review: a run whose materials strayed past its recipe's
     *    tolerance at End.
     *  - event_counts: the hourly counts (the final station's counter) against
     *    the hour's target.
     *  - event_quantities: the stage a figure belongs to, a reject's reason and
     *    where it went (waste / rework / keep / stock).
     *  - event_status_logs / event_pause_activities: the station a line stop or
     *    a station stop is charged to, with its name kept for history.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('feeds_event_id')->nullable()->after('to_plan_id')
                ->constrained('events')->nullOnDelete();
            $table->boolean('needs_review')->default(false)->after('status');
        });

        Schema::create('event_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->dateTime('counted_at');
            $table->decimal('counter', 15, 4);
            $table->decimal('quantity', 15, 4);
            $table->decimal('target', 15, 4)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'counted_at']);
        });

        Schema::table('event_quantities', function (Blueprint $table) {
            $table->foreignId('recipe_stage_id')->nullable()->after('recipe_side_product_id')
                ->constrained('recipe_stages')->nullOnDelete();
            $table->string('stage_name')->nullable()->after('recipe_stage_id');
            $table->string('reason')->nullable()->after('percentage');
            $table->string('disposition', 20)->nullable()->after('reason');
        });

        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->foreignId('line_station_id')->nullable()->after('event_id')
                ->constrained('line_stations')->nullOnDelete();
            $table->string('station_name')->nullable()->after('line_station_id');
        });

        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->foreignId('line_station_id')->nullable()->after('event_type_id')
                ->constrained('line_stations')->nullOnDelete();
            $table->string('station_name')->nullable()->after('line_station_id');
            $table->unsignedSmallInteger('machine_number')->nullable()->after('station_name');
        });
    }

    public function down(): void
    {
        Schema::table('event_pause_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('line_station_id');
            $table->dropColumn(['station_name', 'machine_number']);
        });

        Schema::table('event_status_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('line_station_id');
            $table->dropColumn('station_name');
        });

        Schema::table('event_quantities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipe_stage_id');
            $table->dropColumn(['stage_name', 'reason', 'disposition']);
        });

        Schema::dropIfExists('event_counts');

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('feeds_event_id');
            $table->dropColumn('needs_review');
        });
    }
};
