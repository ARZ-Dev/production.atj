<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            // The plan of the day the event ends on, when it runs past
            // midnight; null for same-day events. Kept in sync by
            // PlanCarryOverService wherever event times are written.
            $table->foreignId('to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            // A mix planned for a line run: the run it feeds.
            $table->foreignId('feeds_event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('event_type_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('recipe_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreignId('recipe_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('production_line_id')->nullable()->constrained()->nullOnDelete();
            // The preparation, line or packaging it is placed on.
            $table->nullableMorphs('placeable');
            $table->string('name');
            // Full datetimes so events spanning midnight (or several days)
            // are unambiguous. Null until the event is placed on the board.
            $table->dateTime('from_time')->nullable();
            $table->dateTime('to_time')->nullable();
            $table->string('batch_count')->nullable();
            $table->string('planned_duration')->nullable();
            $table->string('calculated_duration')->nullable();
            $table->string('status')->nullable();
            $table->boolean('needs_review')->default(false);
            // The add-event popup's key: every event one save creates carries it.
            $table->uuid('submission_key')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
