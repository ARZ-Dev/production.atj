<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_production_line', function (Blueprint $table) {
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->foreignId('production_line_id')->constrained('production_lines')->cascadeOnDelete();
            $table->primary(['line_id', 'production_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_production_line');
    }
};
