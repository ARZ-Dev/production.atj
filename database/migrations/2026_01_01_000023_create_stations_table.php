<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The machines (Oven 1, Flow Wrap 3 …), each of one station type with its
        // own rate, in a department and its factory.
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_type_id')->constrained('station_types')->restrictOnDelete();
            // The parent's department and its warehouse of type factory, no foreign key.
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('factory_id')->nullable()->index();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            // Per machine, in the basic unit of what its stage makes (packets,
            // cans …; kg for dough stages).
            $table->decimal('rate', 15, 4)->nullable();
            $table->string('rate_period', 10)->default('hour');   // hour | minute
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
