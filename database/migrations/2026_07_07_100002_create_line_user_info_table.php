<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_user_info', function (Blueprint $table) {
            $table->foreignId('user_info_id')->constrained()->cascadeOnDelete();
            $table->foreignId('line_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_info_id', 'line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_user_info');
    }
};
