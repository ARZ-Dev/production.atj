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
        Schema::create('event_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('action');                  // start | pause | resume | terminate
            $table->string('from_status')->nullable(); // null = planned
            $table->string('to_status');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_status_logs');
    }
};
