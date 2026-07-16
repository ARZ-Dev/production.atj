<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipe_types', function (Blueprint $table) {
            $table->json('side_item_type_ids')->nullable()->after('item_type_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_types', function (Blueprint $table) {
            $table->dropColumn('side_item_type_ids');
        });
    }
};
