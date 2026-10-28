<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same warehouse shape as preparations / lines (see RoutesWarehouses). The
        // warehouse columns are nullable; the form requires them on save.
        Schema::create('packagings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id');  // department in the parent service
            $table->unsignedBigInteger('factory_id');     // warehouse of type "factory"
            $table->unsignedBigInteger('source_warehouse_id')->nullable();
            $table->json('source_warehouse_ids')->nullable();
            $table->json('item_type_warehouses')->nullable();
            $table->unsignedBigInteger('fg_warehouse_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packagings');
    }
};
