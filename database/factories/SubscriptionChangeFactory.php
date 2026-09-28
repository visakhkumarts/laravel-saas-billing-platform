<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\SubscriptionChange>
 */
class SubscriptionChangeFactory extends Factory
{
    public function definition(): array
    {
        $effectiveAt = Carbon::now()
            ->subDays(fake()->numberBetween(1, 15));

        return [
            'subscription_id' => Subscription::factory(),
            'from_plan_id' => Plan::factory(),
            'to_plan_id' => Plan::factory(),
            'effective_at' => $effectiveAt,
        ];
    }
}
