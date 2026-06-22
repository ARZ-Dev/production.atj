<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('item_type_id')->nullable()->after('recipe_id');
            $table->unsignedBigInteger('item_id')->nullable()->after('item_type_id');
            $table->foreignId('recipe_type_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
            $table->foreignId('production_line_id')->nullable()->after('recipe_type_id')->constrained()->nullOnDelete();
            $table->nullableMorphs('placeable');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipe_type_id');
            $table->dropConstrainedForeignId('production_line_id');
            $table->dropMorphs('placeable');
            $table->dropColumn(['item_type_id', 'item_id']);
        });
    }
};
