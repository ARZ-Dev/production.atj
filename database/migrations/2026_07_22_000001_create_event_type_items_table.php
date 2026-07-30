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
        // Standard items (with unit + quantity) consumed by a non-recipe event
        // type, grouped by the event type's item types.
        Schema::create('event_type_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->decimal('quantity', 10, 4);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_type_items');
    }
};
