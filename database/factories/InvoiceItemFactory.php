<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 100000);
        $unitPrice = fake()->randomFloat(6, 0.01, 1);

        return [
            'invoice_id' => Invoice::factory(),
            'plan_id' => Plan::factory(),

            'type' => fake()->randomElement([
                'base',
                'overage',
            ]),

            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => round($quantity * $unitPrice, 2),
        ];
    }
}
