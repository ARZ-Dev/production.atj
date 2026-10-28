<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_status_log_id')->nullable()->constrained()->cascadeOnDelete();
            // Set for source = "emergency": the items consumed while carrying
            // out an emergency event.
            $table->foreignId('event_pause_activity_id')->nullable()->constrained('event_pause_activities')->cascadeOnDelete();
            // The warehouse the quantity moved through.
            $table->unsignedBigInteger('warehouse_id')->nullable();
            // input | output | side_product | emergency, and for line runs
            // input_leftover | stage_output | reject | stage_leftover
            $table->string('source');
            $table->foreignId('recipe_input_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipe_side_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipe_stage_id')->nullable()->constrained('recipe_stages')->nullOnDelete();
            $table->string('stage_name')->nullable();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_unit_id')->nullable();
            // Name snapshots so history can render without calling the items API
            $table->string('item_name')->nullable();
            $table->string('unit_name')->nullable();
            $table->decimal('planned_quantity', 10, 4)->nullable();
            $table->decimal('actual_quantity', 10, 4);
            // End-of-event reconciliation (unverifiable-start event types).
            $table->decimal('actual_used_quantity', 10, 4)->nullable();
            $table->decimal('remaining_quantity', 10, 4)->nullable();
            $table->string('remaining_action')->nullable();   // stock_in | waste
            // The Stock In / Waste document the leftover (or a run's rework) was
            // filed on. The index is named explicitly: the generated name is over
            // MySQL's 64 characters.
            $table->nullableMorphs('remaining_document', 'eq_remaining_document_index');
            $table->decimal('percentage', 8, 2)->nullable(); // variance against planned
            $table->string('reason')->nullable();
            $table->string('disposition', 20)->nullable();
            // When the movement hit stock; null while it is still held in process.
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_quantities');
    }
};
