<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DailyUsageAggregate>
 */
class DailyUsageAggregateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'usage_date' => fake()->dateTimeBetween(
                '-30 days',
                'now'
            )->format('Y-m-d'),
            'total_units' => fake()->numberBetween(1000, 100000),
        ];
    }
}
