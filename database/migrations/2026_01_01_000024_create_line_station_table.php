<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A line's machines, in the order the product passes them.
        Schema::create('line_station', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();

            $table->unique(['line_id', 'station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_station');
    }
};
