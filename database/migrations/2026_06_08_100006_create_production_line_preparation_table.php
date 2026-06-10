<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_line_preparation', function (Blueprint $table) {
            $table->foreignId('production_line_id')->constrained('production_lines')->cascadeOnDelete();
            $table->foreignId('preparation_id')->constrained('preparations')->cascadeOnDelete();
            $table->primary(['production_line_id', 'preparation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_line_preparation');
    }
};
