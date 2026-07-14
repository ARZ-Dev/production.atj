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
        Schema::create('event_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_status_log_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('source'); // input (on start) | output | side_product (on terminate)
            $table->foreignId('recipe_input_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipe_side_product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_unit_id')->nullable();
            // Name snapshots so history can render without calling the items API
            $table->string('item_name')->nullable();
            $table->string('unit_name')->nullable();
            $table->decimal('planned_quantity', 10, 4)->nullable();
            $table->decimal('actual_quantity', 10, 4);
            $table->decimal('percentage', 8, 2)->nullable(); // actual / planned * 100
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_quantities');
    }
};
