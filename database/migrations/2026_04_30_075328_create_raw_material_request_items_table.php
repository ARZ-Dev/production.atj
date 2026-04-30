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
        Schema::create('raw_material_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_id')
                ->constrained('raw_material_requests')
                ->cascadeOnDelete();

            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->restrictOnDelete();

            $table->decimal('quantity', 20, 6);

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            // IMPORTANT → store base quantity for calculations
            $table->decimal('base_quantity', 20, 6);

            $table->foreignId('base_unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_request_items');
    }
};
