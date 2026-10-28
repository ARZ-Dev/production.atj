<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A line run's counts: the last station's counter at a time.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('event_counts');
    }
};
