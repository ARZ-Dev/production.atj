<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A line run: one event for a whole continuous line.
     *
     *  - line_stations: the machines of a line, in the order the product
     *    passes them (Cutting, Cooking, Packaging, Packing …). The rate is per
     *    machine, in the unit the matching recipe stage counts its output in;
     *    the line runs at its slowest station's rate.
     *  - recipe_stages: what a recipe does at each station — matched to the
     *    line's stations by name. Inputs are assigned to the stage that uses
     *    them, and each stage says how much it outputs per batch (for the
     *    rate and the End counts) and where its rejects go.
     */
    public function up(): void
    {
        Schema::create('line_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_id')->constrained('lines')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('name');
            $table->string('machine')->nullable();
            $table->unsignedSmallInteger('machine_count')->default(1);
            $table->decimal('rate', 15, 4)->nullable();
            $table->string('rate_period', 10)->default('hour');   // hour | minute
            $table->string('rate_unit', 50)->nullable();
            $table->timestamps();

            $table->index(['line_id', 'sequence']);
        });

        Schema::create('recipe_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('name');
            $table->decimal('output_quantity', 15, 4);
            $table->string('output_unit', 50)->nullable();
            // Set when the stage's output is the product itself in one of its
            // units (packets before they are boxed): leftovers can then be
            // stocked at End.
            $table->unsignedBigInteger('output_item_unit_id')->nullable();
            $table->string('reject_action', 10)->default('waste');  // waste | rework
            $table->unsignedBigInteger('rework_item_type_id')->nullable();
            $table->unsignedBigInteger('rework_item_id')->nullable();
            $table->unsignedBigInteger('rework_item_unit_id')->nullable();
            $table->decimal('rework_factor', 15, 6)->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['recipe_id', 'sequence']);
        });

        Schema::table('recipe_inputs', function (Blueprint $table) {
            $table->foreignId('recipe_stage_id')->nullable()->after('recipe_id')
                ->constrained('recipe_stages')->nullOnDelete();
        });

        Schema::table('recipes', function (Blueprint $table) {
            // Percent: how far a material may stray from the recipe at End
            // before the run is flagged for review.
            $table->decimal('balance_tolerance', 6, 2)->nullable()->after('batch_volume');
            // Percent: extra dough planned on top of the recipe when mixes are
            // planned for a run, for the line's usual rejects.
            $table->decimal('reject_allowance', 6, 2)->nullable()->after('balance_tolerance');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['balance_tolerance', 'reject_allowance']);
        });

        Schema::table('recipe_inputs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipe_stage_id');
        });

        Schema::dropIfExists('recipe_stages');
        Schema::dropIfExists('line_stations');
    }
};
