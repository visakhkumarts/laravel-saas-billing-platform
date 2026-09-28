<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DailyUsageAggregate;
use App\Models\Merchant;
use App\Models\UsageEvent;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Technologies',
            'status' => 'active',
        ]);

        $starterPlan = $merchant->plans()->create([
            'name' => 'Starter',
            'base_price' => 5000,
            'billing_cycle' => 'monthly',
            'included_units' => 50000,
            'overage_rate' => 0.100000,
            'status' => 'active',
        ]);

        $growthPlan = $merchant->plans()->create([
            'name' => 'Growth',
            'base_price' => 10000,
            'billing_cycle' => 'monthly',
            'included_units' => 100000,
            'overage_rate' => 0.080000,
            'status' => 'active',
        ]);

        $enterprisePlan = $merchant->plans()->create([
            'name' => 'Enterprise',
            'base_price' => 25000,
            'billing_cycle' => 'monthly',
            'included_units' => 500000,
            'overage_rate' => 0.050000,
            'status' => 'active',
        ]);

        $customers = Customer::factory()
            ->count(10)
            ->for($merchant)
            ->create();

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd = Carbon::now()->endOfMonth();

        foreach ($customers as $index => $customer) {

            /*
             * Customer 1 gets Enterprise.
             * The remaining customers get Growth.
             */
            $plan = $index === 0
                ? $enterprisePlan
                : $growthPlan;

            Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'started_at' => $periodStart,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'status' => 'active',
            ]);
        }

        /*
         * Create usage for the current month.
         *
         * Customer 2 is intentionally given high usage so that
         * the dashboard can demonstrate projected overage.
         */
        $today = Carbon::now()->startOfDay();

        foreach ($customers as $index => $customer) {

            for ($day = 0; $day < $today->day; $day++) {

                $usageDate = $periodStart->copy()->addDays($day);

                /*
                 * Customer 2:
                 * High usage to demonstrate projected overage.
                 */
                if ($index === 1) {
                    $units = 4200;

                /*
                 * Customer 3:
                 * Second highest usage.
                 */
                } elseif ($index === 2) {
                    $units = 3000;

                /*
                 * Customer 4:
                 * Third highest usage.
                 */
                } elseif ($index === 3) {
                    $units = 2200;

                /*
                 * Customer 5:
                 * Fourth highest usage.
                 */
                } elseif ($index === 4) {
                    $units = 1700;

                /*
                 * Customer 6:
                 * Fifth highest usage.
                 */
                } elseif ($index === 5) {
                    $units = 1300;

                /*
                 * Other customers have lower usage.
                 */
                } else {
                    $units = 500;
                }

                UsageEvent::create([
                    'merchant_id' => $merchant->id,
                    'customer_id' => $customer->id,
                    'idempotency_key' =>
                        "seed-{$customer->id}-{$usageDate->toDateString()}",
                    'units' => $units,
                    'usage_date' => $usageDate,
                ]);
            }
        }

        /*
         * Create previous-month usage for Customer 7.
         *
         * This is intentionally much higher than the current month,
         * so the dashboard can show the customer under churn risk.
         */
        $lastMonthStart = Carbon::now()
            ->subMonth()
            ->startOfMonth();

        $lastMonthEnd = Carbon::now()
            ->subMonth()
            ->endOfMonth();

        for (
            $date = $lastMonthStart->copy();
            $date->lte($lastMonthEnd);
            $date->addDay()
        ) {
            UsageEvent::create([
                'merchant_id' => $merchant->id,
                'customer_id' => $customers[6]->id,
                'idempotency_key' =>
                    "seed-last-month-{$customers[6]->id}-{$date->toDateString()}",
                'units' => 3000,
                'usage_date' => $date,
            ]);
        }

        /*
         * Aggregate the seeded usage.
         *
         * This uses the same daily aggregation structure used by the
         * application so the dashboard reads from the aggregate table.
         */
        $usageDates = UsageEvent::query()
            ->select('usage_date')
            ->distinct()
            ->pluck('usage_date');

        foreach ($usageDates as $usageDate) {

            $usageGroups = UsageEvent::query()
                ->selectRaw(
                    'merchant_id, customer_id, usage_date, SUM(units) as total_units'
                )
                ->whereDate('usage_date', $usageDate)
                ->groupBy(
                    'merchant_id',
                    'customer_id',
                    'usage_date'
                )
                ->get();

            foreach ($usageGroups as $usageGroup) {
                DailyUsageAggregate::updateOrCreate(
                    [
                        'customer_id' => $usageGroup->customer_id,
                        'usage_date' => $usageGroup->usage_date,
                    ],
                    [
                        'merchant_id' => $usageGroup->merchant_id,
                        'total_units' => $usageGroup->total_units,
                    ]
                );
            }
        }
    }
}