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
            $table->decimal('output_quantity', 15, 4);
            $table->string('output_unit', 50)->nullable();
            // Set when the stage's output is the product itself in one of its
            // units (packets before they are boxed): leftovers can then be
            // stocked at End.
            $table->unsignedBigInteger('output_item_unit_id')->nullable();
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
