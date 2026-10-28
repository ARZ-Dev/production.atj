<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One capacity per item unit. Item units are per-item rows in the parent
        // (item_units.item_id), so item_unit_id alone identifies the item too;
        // item_id and item_type_id are kept so a packaging's capacities can be
        // grouped and listed without an API round-trip.
        Schema::create('packaging_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packaging_id')->constrained('packagings')->cascadeOnDelete();
            $table->unsignedBigInteger('item_type_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id');
            $table->decimal('capacity', 15, 2)->comment('Capacity per hour, in this unit');
            $table->timestamps();

            $table->unique(['packaging_id', 'item_unit_id'], 'packaging_capacities_unit_unique');
            $table->index(['packaging_id', 'item_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_capacities');
    }
};
