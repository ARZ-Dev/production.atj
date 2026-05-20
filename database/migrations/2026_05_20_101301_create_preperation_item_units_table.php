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
        Schema::create('preperation_item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preperation_item_id')->constrained('preperation_items')->cascadeOnDelete();
            $table->string('name');
            $table->string('symbol');
            $table->integer('basic')->nullable();
            $table->integer('is_box')->nullable();
            $table->decimal('box_qty', 10, 4)->default(0);
            $table->tinyInteger('price_type')->default(0); // 0=ALL, 1=B2B/B2C, 2=POS
            $table->decimal('formula', 10, 4)->default(0);
            $table->decimal('price', 15, 4)->nullable();
            $table->decimal('weight', 10, 4)->nullable();
            $table->decimal('volume', 10, 4)->nullable();
            $table->decimal('vat', 5, 2)->nullable();
            $table->integer('not_pos')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preperation_item_units');
    }
};
