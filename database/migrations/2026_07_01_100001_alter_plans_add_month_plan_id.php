<?php

use App\Models\MonthPlan;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('month_plan_id')
                ->nullable()
                ->after('id')
                ->constrained('month_plans')
                ->nullOnDelete();
        });

        // Backfill: group every existing plan that already has a factory into a
        // month plan so the calendar keeps working after this change.
        Plan::withTrashed()
            ->whereNotNull('factory_id')
            ->chunkById(200, function ($plans) {
                foreach ($plans as $plan) {
                    if ($plan->month_plan_id) {
                        continue;
                    }

                    $date = Carbon::parse($plan->date);

                    $monthPlan = MonthPlan::firstOrCreate([
                        'factory_id' => $plan->factory_id,
                        'year'       => $date->year,
                        'month'      => $date->month,
                    ]);

                    $plan->month_plan_id = $monthPlan->id;
                    $plan->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropForeign(['month_plan_id']);
            $table->dropColumn('month_plan_id');
        });
    }
};
