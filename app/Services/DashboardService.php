<?php

namespace App\Services;

use App\Models\DailyUsageAggregate;
use App\Models\Merchant;
use App\Models\Subscription;
use Carbon\Carbon;
use App\Models\Customer;

class DashboardService
{
    public function getDashboard(Merchant $merchant): array
    {
        $now = now();

        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->addMonth();

        /*
         * Active subscriptions for this merchant.
         */
        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereHas('customer', function ($query) use ($merchant) {
                $query->where('merchant_id', $merchant->id);
            })
            ->with([
                'customer:id,name,email',
                'plan',
            ])
            ->get();

        /*
         * 1. Current cycle usage and allowance
         *
         * The dashboard is merchant-level, so we total the usage and
         * included units of the merchant's active subscriptions.
         */
        $currentCycleUsage = 0;
        $currentCycleIncludedUnits = 0;

        foreach ($subscriptions as $subscription) {
            $usage = DailyUsageAggregate::query()
                ->where('customer_id', $subscription->customer_id)
                ->whereDate(
                    'usage_date',
                    '>=',
                    Carbon::parse($subscription->current_period_start)
                )
                ->whereDate(
                    'usage_date',
                    '<',
                    min(
                        $now,
                        Carbon::parse($subscription->current_period_end)
                    )
                )
                ->sum('total_units');

            $currentCycleUsage += (int) $usage;
            $currentCycleIncludedUnits += (int) $subscription->plan->included_units;
        }

        /*
         * 2. Active plan
         *
         * If all active subscriptions use the same plan, show that plan.
         * Otherwise show "Multiple plans".
         */
        $activePlans = $subscriptions
            ->pluck('plan')
            ->unique('id')
            ->values();

        if ($activePlans->count() === 1) {
            $activePlan = [
                'name' => $activePlans->first()->name,
                'billing_cycle' => $activePlans->first()->billing_cycle,
            ];
        } else {
            $activePlan = [
                'name' => 'Multiple plans',
                'billing_cycle' => null,
            ];
        }

        /*
         * 3. Top 5 customers by current-month usage
         */
        $topCustomers = DailyUsageAggregate::query()
            ->where('merchant_id', $merchant->id)
            ->whereDate('usage_date', '>=', $startOfMonth)
            ->whereDate('usage_date', '<', $endOfMonth)
            ->selectRaw(
                'customer_id, SUM(total_units) as total_units'
            )
            ->with('customer:id,name,email')
            ->groupBy('customer_id')
            ->orderByDesc('total_units')
            ->limit(5)
            ->get();

        /*
         * Load the active subscription for the top customers so that
         * we can calculate their percentage of included allowance.
         */
        $topCustomerIds = $topCustomers
            ->pluck('customer_id')
            ->values();

        $topCustomerSubscriptions = Subscription::query()
            ->whereIn('customer_id', $topCustomerIds)
            ->where('status', 'active')
            ->with('plan')
            ->get()
            ->keyBy('customer_id');

        $topCustomers->each(function ($customer) use ($topCustomerSubscriptions) {
            $subscription = $topCustomerSubscriptions->get(
                $customer->customer_id
            );

            $includedUnits = $subscription
                ? (int) $subscription->plan->included_units
                : 0;

            $customer->percentage_of_allowance = $includedUnits > 0
                ? round(
                    ($customer->total_units / $includedUnits) * 100,
                    2
                )
                : 0;
        });

        /*
         * 4. Projected overage revenue
         *
         * Projection is calculated separately for each active subscription
         * based on its current billing cycle.
         */
        $projectedOverageRevenue = 0;

        foreach ($subscriptions as $subscription) {
            $periodStart = Carbon::parse(
                $subscription->current_period_start
            );

            $periodEnd = Carbon::parse(
                $subscription->current_period_end
            );

            $cycleDays = max(
                1,
                $periodStart->diffInDays($periodEnd)
            );

            $elapsedDays = max(
                1,
                $periodStart->diffInDays(
                    min($now, $periodEnd)
                )
            );

            $usage = DailyUsageAggregate::query()
                ->where('customer_id', $subscription->customer_id)
                ->whereDate('usage_date', '>=', $periodStart)
                ->whereDate('usage_date', '<', $now)
                ->sum('total_units');

            $projectedUsage =
                ($usage / $elapsedDays) * $cycleDays;

            $includedUnits =
                (int) $subscription->plan->included_units;

            if ($projectedUsage > $includedUnits) {
                $overageUnits =
                    $projectedUsage - $includedUnits;

                $projectedOverageRevenue +=
                    $overageUnits *
                    (float) $subscription->plan->overage_rate;
            }
        }

        /*
         * 5. Customers with more than 50% usage drop
         */
        $lastMonthStart = $startOfMonth->copy()->subMonth();
        $lastMonthEnd = $startOfMonth->copy();

        $currentUsage = DailyUsageAggregate::query()
            ->where('merchant_id', $merchant->id)
            ->whereDate('usage_date', '>=', $startOfMonth)
            ->whereDate('usage_date', '<', $endOfMonth)
            ->selectRaw(
                'customer_id, SUM(total_units) as total_units'
            )
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $lastMonthUsage = DailyUsageAggregate::query()
            ->where('merchant_id', $merchant->id)
            ->whereDate('usage_date', '>=', $lastMonthStart)
            ->whereDate('usage_date', '<', $lastMonthEnd)
            ->selectRaw(
                'customer_id, SUM(total_units) as total_units'
            )
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $usageDropCustomers = [];

        $customerIds = $lastMonthUsage
            ->keys()
            ->merge($currentUsage->keys())
            ->unique()
            ->values();

        $customersById = Customer::query()
            ->whereIn('id', $customerIds)
            ->pluck('name', 'id');

        foreach ($lastMonthUsage as $customerId => $previous) {
            $previousUsage = (float) $previous->total_units;

            if ($previousUsage <= 0) {
                continue;
            }

            $current = $currentUsage->get($customerId);

            $currentUnits = $current
                ? (float) $current->total_units
                : 0;

            $dropPercentage =
                (($previousUsage - $currentUnits) / $previousUsage) * 100;

            if ($dropPercentage > 50) {
                $usageDropCustomers[] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customersById->get(
                        (int) $customerId,
                        'Unknown Customer'
                    ),
                    'previous_month_usage' => $previousUsage,
                    'current_month_usage' => $currentUnits,
                    'drop_percentage' => round(
                        $dropPercentage,
                        2
                    ),
                ];
            }
        }

        /*
         * 6. Daily usage trend for the last 30 days
         */
        $trendStart = $now->copy()
            ->subDays(29)
            ->startOfDay();

        $dailyUsageTrend = DailyUsageAggregate::query()
            ->where('merchant_id', $merchant->id)
            ->whereDate('usage_date', '>=', $trendStart)
            ->whereDate('usage_date', '<=', $now)
            ->selectRaw(
                'usage_date, SUM(total_units) as total_units'
            )
            ->groupBy('usage_date')
            ->orderBy('usage_date')
            ->get()
            ->map(function ($usage) {
                return [
                    'date' => Carbon::parse(
                        $usage->usage_date
                    )->toDateString(),

                    'total_units' => (int) $usage->total_units,
                ];
            })
            ->values();

        return [
            'current_cycle_usage' => $currentCycleUsage,
            'current_cycle_included_units' => $currentCycleIncludedUnits,
            'active_plan' => $activePlan,
            'top_customers' => $topCustomers,
            'projected_overage_revenue' => round($projectedOverageRevenue, 2),
            'usage_drop_customers' => $usageDropCustomers,
            'daily_usage_trend' => $dailyUsageTrend,
        ];
    }
}
