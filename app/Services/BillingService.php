<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\DailyUsageAggregate;
use App\Models\SubscriptionChange;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BillingService
{
    public function getPlanSegments(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd): Collection
    {

        $changes = SubscriptionChange::query()
            ->where('subscription_id', $subscription->id)
            ->where('effective_at', '>', $periodStart)
            ->where('effective_at', '<', $periodEnd)
            ->orderBy('effective_at')
            ->get();

        $currentPlan = $this->getCachedPlan($subscription->plan_id);

        if ($changes->isNotEmpty()) {
            $currentPlan = $this->getCachedPlan(
                $changes->first()->from_plan_id
            );
        }

        $segments = collect();
        $segmentStart = $periodStart;

        foreach ($changes as $change) {
            $segments->push([
                'plan' => $currentPlan,
                'start' => $segmentStart,
                'end' => $change->effective_at,
            ]);

            $currentPlan = $this->getCachedPlan($change->to_plan_id);
            $segmentStart = $change->effective_at;
        }

        $segments->push([
            'plan' => $currentPlan,
            'start' => $segmentStart,
            'end' => $periodEnd,
        ]);

        return $segments;
    }

    public function calculateProratedCharges(Collection $segments, Carbon $periodStart, Carbon $periodEnd): array
    {

        $totalDays = $periodStart->diffInDays($periodEnd);

        $charges = [];
        $totalCharge = 0;

        foreach ($segments as $segment) {
            $segmentDays = $segment['start']->diffInDays($segment['end']);

            $planPrice = (float) $segment['plan']['base_price'];

            $charge = ($planPrice * $segmentDays) / $totalDays;

            $charges[] = [
                'plan_id' => $segment['plan']['id'],
                'amount' => round($charge, 2),
            ];

            $totalCharge += round($charge, 2);
        }

        return [
            'items' => $charges,
            'total' => round($totalCharge, 2),
        ];
    }

    public function getSegmentUsage(Subscription $subscription, Carbon $start, Carbon $end): int
    {

        return DailyUsageAggregate::query()
            ->where('customer_id', $subscription->customer_id)
            ->whereDate('usage_date', '>=', $start)
            ->whereDate('usage_date', '<', $end)
            ->sum('total_units');
    }

    public function calculateSegmentOverage($plan, int $usage, int $segmentDays, int $totalDays): float
    {

        $includedUnits = ($plan['included_units'] * $segmentDays) / $totalDays;

        if ($usage <= $includedUnits) {
            return 0;
        }

        $overageUnits = $usage - $includedUnits;
        return round($overageUnits * $plan['overage_rate'],2);
    }

    public function calculateOverage(Subscription $subscription, Collection $segments, Carbon $periodStart, Carbon $periodEnd): array
    {

        $totalOverage = 0;
        $items = [];

        $totalDays = $periodStart->diffInDays($periodEnd);

        foreach ($segments as $segment) {

            $usage = $this->getSegmentUsage(
                $subscription,
                $segment['start'],
                $segment['end']
            );

            $segmentDays = $segment['start']->diffInDays(
                $segment['end']
            );

            $overage = $this->calculateSegmentOverage(
                $segment['plan'],
                $usage,
                $segmentDays,
                $totalDays
            );

            if ($overage > 0) {
                $items[] = [
                    'plan_id' => $segment['plan']['id'],
                    'amount' => $overage,
                ];
            }

            $totalOverage += $overage;
        }

        return [
            'items' => $items,
            'total' => round($totalOverage, 2),
        ];
    }

    public function calculateTotal(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd): array
    {

        $segments = $this->getPlanSegments(
            $subscription,
            $periodStart,
            $periodEnd
        );

        $proration = $this->calculateProratedCharges(
            $segments,
            $periodStart,
            $periodEnd
        );

        $overage = $this->calculateOverage(
            $subscription,
            $segments,
            $periodStart,
            $periodEnd
        );

        return [
            'base_items' => $proration['items'],
            'base_amount' => $proration['total'],
            'overage_items' => $overage['items'],
            'overage_amount' => $overage['total'],
            'total_amount' => round(
                $proration['total'] + $overage['total'],
                2
            ),
        ];
    }

    private function getCachedPlan(int $planId): array
    {
        return Cache::remember(
            "plan:{$planId}",
            now()->addHour(),
            function () use ($planId) {
                $plan = \App\Models\Plan::findOrFail($planId);

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'base_price' => (float) $plan->base_price,
                    'included_units' => (int) $plan->included_units,
                    'overage_rate' => (float) $plan->overage_rate,
                    'billing_cycle' => $plan->billing_cycle,
                ];
            }
        );
    }
}
