<?php

namespace App\Services;

use App\Models\MonthPlan;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlanCarryOverService
{
    /**
     * Whether the event runs strictly past the midnight of the day it
     * starts on. An event ending exactly at 00:00 finishes with its day
     * and doesn't spill over.
     */
    public function crossesMidnight(?string $fromTime, ?string $toTime): bool
    {
        if (!$fromTime || !$toTime) {
            return false;
        }

        return Carbon::parse($toTime)->gt(Carbon::parse($fromTime)->startOfDay()->addDay());
    }

    /**
     * Resolve events.to_plan_id for the given times: the plan of the day
     * the event ends on, creating plans — and month plans — for every
     * extra day the event touches along the way.
     *
     * Returns ['to_plan_id' => ?int, 'created' => Collection<Plan>] where
     * created holds only newly created plans, each with its monthPlan
     * relation set so monthPlan->wasRecentlyCreated is inspectable.
     */
    public function carryOverLink(Plan $plan, ?string $fromTime, ?string $toTime): array
    {
        if (!$this->crossesMidnight($fromTime, $toTime)) {
            return ['to_plan_id' => null, 'created' => collect()];
        }

        $dayPlans = $this->ensurePlansThrough($plan, $toTime);

        return [
            'to_plan_id' => $dayPlans->last()?->id,
            'created'    => $dayPlans->filter(fn (Plan $p) => $p->wasRecentlyCreated)->values(),
        ];
    }

    /**
     * Ensure a plan exists (same factory) for every calendar day after the
     * event's own plan day, up to and including the day the event ends on.
     * Usually that's just the next day; an event longer than 24h yields one
     * plan per day it touches.
     */
    public function ensurePlansThrough(Plan $plan, string $toTime): Collection
    {
        if (!$plan->factory_id) {
            return collect();
        }

        $end = Carbon::parse($toTime);

        // Ending exactly at midnight occupies nothing on that calendar day.
        if ($end->format('H:i:s') === '00:00:00') {
            $end = $end->subDay();
        }

        $end   = $end->startOfDay();
        $plans = collect();

        for ($day = Carbon::parse($plan->date)->startOfDay()->addDay(); $day->lte($end); $day = $day->copy()->addDay()) {
            $plans->push($this->ensurePlanFor($plan, $day));
        }

        return $plans;
    }

    protected function ensurePlanFor(Plan $source, Carbon $date): Plan
    {
        $existing = Plan::where('factory_id', $source->factory_id)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();

        if ($existing) {
            return $existing;
        }

        $monthPlan = MonthPlan::firstOrCreate(
            [
                'factory_id' => $source->factory_id,
                'year'       => $date->year,
                'month'      => $date->month,
            ],
            ['factory_name' => $source->monthPlan?->factory_name]
        );

        $plan = Plan::create([
            'date'          => $date->format('Y-m-d'),
            'factory_id'    => $source->factory_id,
            'month_plan_id' => $monthPlan->id,
        ]);

        return $plan->setRelation('monthPlan', $monthPlan);
    }

    /**
     * Human sentence for what was auto-created, e.g. "the monthly plan for
     * August 2026 and a plan for 01 August 2026 were created automatically."
     * Null when nothing was created.
     */
    public function describeCreatedPlans(Collection $createdPlans): ?string
    {
        $createdPlans = $createdPlans->unique('id')->values();

        if ($createdPlans->isEmpty()) {
            return null;
        }

        $dates    = $createdPlans->map(fn (Plan $p) => Carbon::parse($p->date)->format('d F Y'));
        $planPart = ($dates->count() === 1 ? 'a plan for ' : 'plans for ') . $dates->join(', ', ' and ');

        $months = $createdPlans
            ->map(fn (Plan $p) => $p->monthPlan)
            ->filter(fn (?MonthPlan $mp) => $mp && $mp->wasRecentlyCreated)
            ->unique('id')
            ->map(fn (MonthPlan $mp) => $mp->period_label);

        if ($months->isEmpty()) {
            return $planPart . ($dates->count() === 1 ? ' was' : ' were') . ' created automatically.';
        }

        return ($months->count() === 1 ? 'the monthly plan for ' : 'monthly plans for ')
            . $months->join(', ', ' and ')
            . " and {$planPart} were created automatically.";
    }
}
