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
        Schema::table('recipe_inputs', function (Blueprint $table) {
            $table->renameColumn('input_type', 'item_type_id');
        });

        // The column stores item type ids coming from the external API,
        // so it needs to hold full-size ids, not a tinyint.
        Schema::table('recipe_inputs', function (Blueprint $table) {
            $table->unsignedBigInteger('item_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_inputs', function (Blueprint $table) {
            $table->tinyInteger('item_type_id')->nullable()->change();
        });

        Schema::table('recipe_inputs', function (Blueprint $table) {
            $table->renameColumn('item_type_id', 'input_type');
        });
    }
};
