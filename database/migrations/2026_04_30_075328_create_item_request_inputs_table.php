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
        Schema::create('item_request_inputs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_id')
                ->constrained('item_requests')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_unit_id');
            $table->unsignedBigInteger('base_unit_id');
            $table->decimal('quantity', 20, 6);


            // IMPORTANT → store base quantity for calculations
            $table->decimal('base_quantity', 20, 6);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_request_inputs');
    }
};
