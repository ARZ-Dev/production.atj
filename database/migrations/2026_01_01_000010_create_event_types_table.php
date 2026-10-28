<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#818cf8');
            $table->boolean('has_recipe')->default(false);
            // "On start event used items can't be verified": the event is started
            // on its planned quantities and the leftover is settled at End.
            $table->boolean('start_items_unverifiable')->default(false);
            $table->integer('duration')->nullable();
            $table->json('item_type_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }
};
