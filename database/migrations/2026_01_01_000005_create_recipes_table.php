<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('item_type_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->integer('batch');
            $table->decimal('batch_weight', 10, 4)->nullable();
            $table->decimal('batch_volume', 10, 4)->nullable();
            // Percent: how far a material may stray from the recipe at the end of
            // a line run before the run is flagged for review.
            $table->decimal('balance_tolerance', 6, 2)->nullable();
            // Percent: extra dough planned on top of the recipe when mixes are
            // planned for a run, for the line's usual rejects.
            $table->decimal('reject_allowance', 6, 2)->nullable();
            $table->decimal('quantity_per_batch', 10, 4)->nullable();
            $table->boolean('status')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
