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
        Schema::create('guide_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_section_id')->constrained()->cascadeOnDelete();
            $table->string('title_en')->nullable();
            $table->string('title_pr')->nullable();
            $table->string('subtitle_en')->nullable();
            $table->string('subtitle_pr')->nullable();
            $table->longText('content_en')->nullable();
            $table->longText('content_pr')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_blocks');
    }
};
