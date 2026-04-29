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
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // Kilogram, Gram, Liter, Piece
            $table->string('symbol', 20); // kg, g, L, pcs

            $table->enum('type', [
                'weight',
                'volume',
                'count',
            ]);

            // Example:
            // Gram has base_unit_id = Kilogram
            // conversion_factor_to_base = 0.001
            $table->foreignId('base_unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->decimal('conversion_factor_to_base', 20, 8)->default(1);

            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['symbol', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
