<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The lines of a stock in / stock out / waste / transfer.
        Schema::create('report_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->cascadeOnDelete();
            $table->foreignId('stock_out_id')->nullable()->constrained('stock_outs')->cascadeOnDelete();
            $table->foreignId('waste_id')->nullable()->constrained('wastes')->cascadeOnDelete();
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->string('received_quantity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_items');
    }
};
