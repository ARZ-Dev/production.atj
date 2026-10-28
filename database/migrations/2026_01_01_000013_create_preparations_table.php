<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warehouses live in the parent service (see RoutesWarehouses): a list of
        // source warehouses with the first kept in the scalar column as the
        // default, a per-item-type override map, and one output warehouse.
        Schema::create('preparations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('rm_warehouse_id');
            $table->json('rm_warehouse_ids')->nullable();
            $table->unsignedBigInteger('fg_warehouse_id');
            // { "<itemTypeId>": <warehouseId> } — where that item type is consumed from.
            $table->json('item_type_warehouses')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preparations');
    }
};
