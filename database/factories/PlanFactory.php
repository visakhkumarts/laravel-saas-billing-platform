<?php

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->randomElement([
                'Starter',
                'Growth',
                'Professional',
                'Enterprise',
            ]),
            'base_price' => fake()->randomElement([
                4999.00,
                9999.00,
                19999.00,
                49999.00,
            ]),
            'billing_cycle' => 'monthly',
            'included_units' => fake()->randomElement([
                10000,
                50000,
                100000,
                500000,
            ]),
            'overage_rate' => fake()->randomElement([
                0.100000,
                0.080000,
                0.050000,
                0.020000,
            ]),
            'status' => 'active',
        ];
    }
}