<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // The machine that stopped a line run (pause).
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->string('station_name')->nullable();
            $table->string('action');                  // start | pause | resume | terminate
            $table->string('from_status')->nullable(); // null = planned
            $table->string('to_status');
            // User-entered time of the status change (defaults to now in the
            // UI); created_at stays the insert time.
            $table->dateTime('happened_at')->nullable();
            $table->integer('actual_duration')->nullable(); // minutes actually paused (set on resume)
            $table->text('reason')->nullable();               // pause/resume reason
            $table->text('notes')->nullable();                // start/terminate notes
            // Snapshot of the acting user (users live in the external auth service)
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_status_logs');
    }
};
