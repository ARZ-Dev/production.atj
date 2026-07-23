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
        Schema::table('warehouse_inventories', function (Blueprint $table) {
            $table->decimal('quantity_pending_in', 10, 2)->default(0)->after('quantity');
            $table->decimal('quantity_pending_out', 10, 2)->default(0)->after('quantity_pending_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_inventories', function (Blueprint $table) {
            $table->dropColumn(['quantity_pending_in', 'quantity_pending_out']);
        });
    }
};
