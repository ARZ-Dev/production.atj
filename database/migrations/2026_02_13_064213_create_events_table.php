<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('event_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('planned_duration')->nullable();
            $table->string('calculated_duration')->nullable();
            $table->string('status')->default('started');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
