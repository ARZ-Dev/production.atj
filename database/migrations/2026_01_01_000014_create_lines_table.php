<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same warehouse shape as preparations (see RoutesWarehouses).
        Schema::create('lines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('sfg_warehouse_id');
            $table->json('sfg_warehouse_ids')->nullable();
            $table->unsignedBigInteger('fg_warehouse_id');
            $table->json('item_type_warehouses')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lines');
    }
};
