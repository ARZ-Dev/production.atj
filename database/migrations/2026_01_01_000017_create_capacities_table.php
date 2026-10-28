<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A preparation's or line's capacity: one per item, in its basic unit;
        // item_unit_id records the unit it was entered in.
        Schema::create('capacities', function (Blueprint $table) {
            $table->id();
            $table->morphs('capacityable');
            $table->unsignedBigInteger('item_type_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->decimal('capacity', 10, 2)->comment('Output per hour');
            $table->timestamps();

            $table->unique(['capacityable_type', 'capacityable_id', 'item_id'], 'unique_capacity_per_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacities');
    }
};
