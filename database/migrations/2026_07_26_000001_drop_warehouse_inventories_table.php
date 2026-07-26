<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warehouse inventory has moved to the parent (auth-service) database and is now
 * accessed only through its API. This drops the local copy.
 *
 * NOTE: run this only AFTER any existing rows have been migrated to the parent DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('warehouse_inventories');
    }

    public function down(): void
    {
        Schema::create('warehouse_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('quantity_pending_in', 10, 2)->default(0);
            $table->decimal('quantity_pending_out', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
