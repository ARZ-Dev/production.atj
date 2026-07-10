<?php

namespace App\Services;

use App\Models\MonthPlan;
use App\Models\Plan;
use Carbon\Carbon;

class PlanCarryOverService
{
    /**
     * An event whose to_time wrapped past 24:00 reads earlier than (or equal
     * to) its from_time — the same convention the plan board uses to detect
     * carry-over events.
     */
    public function crossesMidnight(?string $fromTime, ?string $toTime): bool
    {
        if (!$fromTime || !$toTime) {
            return false;
        }

        return Carbon::parse($toTime)->format('H:i:s') <= Carbon::parse($fromTime)->format('H:i:s');
    }

    /**
     * An event crossing midnight spills onto the next calendar day, so that
     * day needs a plan for the same factory for the carry-over to land on.
     * When the next day starts a new month (or year), the month plan is
     * created as well. Returns the next-day plan, or null when the source
     * plan has no factory; check wasRecentlyCreated on the result (and its
     * monthPlan relation) to know what was actually created.
     */
    public function ensureNextDayPlan(Plan $plan): ?Plan
    {
        if (!$plan->factory_id) {
            return null;
        }

        $nextDate = Carbon::parse($plan->date)->addDay();

        $existing = Plan::where('factory_id', $plan->factory_id)
            ->whereDate('date', $nextDate->format('Y-m-d'))
            ->first();

        if ($existing) {
            return $existing;
        }

        $monthPlan = MonthPlan::firstOrCreate(
            [
                'factory_id' => $plan->factory_id,
                'year'       => $nextDate->year,
                'month'      => $nextDate->month,
            ],
            ['factory_name' => $plan->monthPlan?->factory_name]
        );

        $nextPlan = Plan::create([
            'date'          => $nextDate->format('Y-m-d'),
            'factory_id'    => $plan->factory_id,
            'month_plan_id' => $monthPlan->id,
        ]);

        return $nextPlan->setRelation('monthPlan', $monthPlan);
    }
}
