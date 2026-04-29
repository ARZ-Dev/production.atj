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
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            // Base unit (VERY IMPORTANT)
            $table->foreignId('base_unit_id')
                ->constrained('units')
                ->cascadeOnDelete();

            // Default purchase unit (optional)
            $table->foreignId('purchase_unit_id')
                ->nullable()
                ->constrained('units')
                ->cascadeOnDelete();

            $table->enum('type', [
                'solid',
                'liquid',
                'countable',
                'other'
            ]);

            // For liquids later (optional)
            $table->decimal('density', 20, 8)->nullable();
            // example: oil = 0.92 kg/L

            $table->decimal('reorder_point', 20, 6)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
