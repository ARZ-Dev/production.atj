<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Item type ids (parent service): what its recipes use, their side
            // products and what they make.
            $table->json('item_type_ids')->nullable();
            $table->json('side_item_type_ids')->nullable();
            $table->json('output_item_type_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_types');
    }
};
