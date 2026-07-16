<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('month_plans', function (Blueprint $table) {
            $table->id();
            // factory_id references a warehouse coming from the external API,
            // so it is stored without a DB-level foreign key (same as plans).
            $table->unsignedBigInteger('factory_id');
            $table->string('factory_name')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['factory_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('month_plans');
    }
};
