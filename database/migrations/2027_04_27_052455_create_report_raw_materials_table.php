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
        Schema::create('report_raw_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->foreignId('raw_material_request_id')->nullable()->constrained('raw_material_requests')->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->onDelete('cascade');
            $table->foreignId('stock_out_id')->nullable()->constrained('stock_outs')->onDelete('cascade');
            $table->foreignId('waste_id')->nullable()->constrained('wastes')->onDelete('cascade');
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->onDelete('cascade');
            $table->decimal('quantity', 15, 2);
            $table->string('received_quantity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_raw_materials');
    }
};
