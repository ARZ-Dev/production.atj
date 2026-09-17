<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flags a recipe event type whose consumed quantities cannot be measured
     * when the event is started — the operator records the planned amounts
     * rather than a verified reading.
     */
    public function up(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->boolean('start_items_unverifiable')->default(false)->after('has_recipe');
        });
    }

    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table) {
            $table->dropColumn('start_items_unverifiable');
        });
    }
};
