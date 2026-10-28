<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Emergency events (Cleaning, Maintenance, …) recorded on an event, and a
        // line run's station stops.
        Schema::create('event_pause_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_status_log_id')->nullable()->constrained()->cascadeOnDelete(); // the pause log
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->nullOnDelete();
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->string('station_name')->nullable();
            // Snapshots so history keeps rendering if the event type changes
            $table->string('event_type_name')->nullable();
            $table->text('reason')->nullable();               // why it happened (required in the UI)
            $table->integer('expected_duration')->nullable(); // minutes, from the event type
            $table->dateTime('happened_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->text('end_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_pause_activities');
    }
};
