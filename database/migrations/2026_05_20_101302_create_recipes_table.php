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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('recipe_type'); // 1 = Preperation - 2 = Production
            $table->string('name');
            $table->foreignId('preperation_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('preperation_item_unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->integer('batch');
            $table->decimal('batch_weight', 10, 4)->nullable();
            $table->decimal('batch_volume', 10, 4)->nullable();
            $table->boolean('status')->default(1);
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
        Schema::dropIfExists('recipes');
    }
};
