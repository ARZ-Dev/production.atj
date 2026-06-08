<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacities', function (Blueprint $table) {
            $table->id();
            $table->morphs('capacityable'); // capacityable_type + capacityable_id
            $table->unsignedBigInteger('item_type_id');
            $table->unsignedBigInteger('item_id');
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
