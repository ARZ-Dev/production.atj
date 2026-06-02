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
        Schema::create('recipe_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('input_type')->nullable();
            // $table->foreignId('preperation_item_id')->nullable()->constrained()->cascadeOnDelete();
            // $table->foreignId('preperation_item_unit_id')->nullable()->constrained()->cascadeOnDelete();
            // $table->foreignId('raw_material_id')->nullable()->constrained()->cascadeOnDelete();
            // $table->foreignId('raw_material_unit_id')->nullable()->constrained('units')->cascadeOnDelete();
            $table->decimal('quantity', 10, 4);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_inputs');
    }
};
