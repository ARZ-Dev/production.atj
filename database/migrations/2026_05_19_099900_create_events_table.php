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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            // The plan of the day the event ends on, when it runs past
            // midnight; null for same-day events. Kept in sync by
            // PlanCarryOverService wherever event times are written.
            $table->foreignId('to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('event_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            // Full datetimes so events spanning midnight (or several days)
            // are unambiguous. Null until the event is placed on the board.
            $table->dateTime('from_time')->nullable();
            $table->dateTime('to_time')->nullable();
            $table->string('batch_count')->nullable();
            $table->string('planned_duration')->nullable();
            $table->string('calculated_duration')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
