<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_line_user_info', function (Blueprint $table) {
            $table->foreignId('user_info_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_line_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_info_id', 'production_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_line_user_info');
    }
};
