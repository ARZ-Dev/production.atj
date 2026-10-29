<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('station_type_id')->nullable()->constrained('station_types')->nullOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('name');   // the station type's, written on save
            // What the stage makes per batch, in one of the product's units
            // (output_item_unit_id), or in kg when that is null (dough stages).
            // Its stations' rates are in the unit's basic unit, so a box or case
            // converts with the unit's formula.
            $table->decimal('output_quantity', 15, 4);
            $table->unsignedBigInteger('output_item_unit_id')->nullable();
            // What's left on this stage at End goes into stock, in that unit
            // (packets not boxed, cans not cased).
            $table->boolean('stock_leftovers')->default(false);
            $table->string('reject_action', 10)->default('waste');  // waste | rework
            $table->unsignedBigInteger('rework_item_type_id')->nullable();
            $table->unsignedBigInteger('rework_item_id')->nullable();
            $table->unsignedBigInteger('rework_item_unit_id')->nullable();
            $table->decimal('rework_factor', 15, 6)->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['recipe_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_stages');
    }
};
