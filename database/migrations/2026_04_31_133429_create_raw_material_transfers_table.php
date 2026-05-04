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
        Schema::create('raw_material_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_request_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('warehouse_from_id');
            $table->unsignedBigInteger('warehouse_to_id');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_transfers');
    }
};
