<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_type_packaging', function (Blueprint $table) {
            $table->foreignId('event_type_id')->constrained('event_types')->cascadeOnDelete();
            $table->foreignId('packaging_id')->constrained('packagings')->cascadeOnDelete();

            $table->primary(['event_type_id', 'packaging_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_type_packaging');
    }
};
