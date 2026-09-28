<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\UsageEvent>
 */
class UsageEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'idempotency_key' => fake()->unique()->uuid(),
            'units' => fake()->numberBetween(100, 10000),
            'usage_date' => fake()->dateTimeBetween(
                '-30 days',
                'now'
            )->format('Y-m-d'),
        ];
    }
}
