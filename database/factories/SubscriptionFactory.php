<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = Carbon::now()->subDays(fake()->numberBetween(1, 60));
        $periodStart = $startedAt->copy();
        $periodEnd = $periodStart->copy()->addMonth()->subSecond();

        return [
            'customer_id' => Customer::factory(),
            'plan_id' => Plan::factory(),
            'started_at' => $startedAt,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'status' => 'active',
        ];
    }
}
