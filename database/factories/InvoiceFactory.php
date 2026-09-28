<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $periodStart = Carbon::now()
            ->subMonth()
            ->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->endOfMonth();

        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),

            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,

            'base_amount' => fake()->randomFloat(2, 5000, 50000),
            'overage_amount' => fake()->randomFloat(2, 0, 10000),
            'total_amount' => 0,

            'status' => 'draft',
            'issued_at' => null,
        ];
    }
}
