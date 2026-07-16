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
        // What was done while an event was paused (Cleaning, Maintenance, …).
        // Several activities can be recorded per pause.
        Schema::create('event_pause_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_status_log_id')->nullable()->constrained()->cascadeOnDelete(); // the pause log
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->nullOnDelete();
            // Snapshots so history keeps rendering if the event type changes
            $table->string('event_type_name')->nullable();
            $table->integer('expected_duration')->nullable(); // minutes, from the event type
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_pause_activities');
    }
};
