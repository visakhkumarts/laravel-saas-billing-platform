<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Models\UsageEvent;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_created_with_correct_amounts_and_items(): void
    {
        $merchant = Merchant::factory()->create();

        $oldPlan = Plan::factory()->create([
            'merchant_id' => $merchant->id,
            'base_price' => 10000,
            'included_units' => 100000,
            'overage_rate' => 0.10,
        ]);

        $newPlan = Plan::factory()->create([
            'merchant_id' => $merchant->id,
            'base_price' => 20000,
            'included_units' => 200000,
            'overage_rate' => 0.05,
        ]);

        $customer = Customer::factory()->create([
            'merchant_id' => $merchant->id,
        ]);

        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $newPlan->id,
        ]);

        SubscriptionChange::factory()->create([
            'subscription_id' => $subscription->id,
            'from_plan_id' => $oldPlan->id,
            'to_plan_id' => $newPlan->id,
            'effective_at' => '2026-09-15',
        ]);

        $invoice = app(InvoiceService::class)->generateInvoice(
            $subscription,
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-10-01')
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'base_amount' => 15333.34,
            'overage_amount' => 0,
            'total_amount' => 15333.34,
            'status' => 'issued',
        ]);

        $this->assertDatabaseCount('invoice_items', 2);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'plan_id' => $oldPlan->id,
            'type' => 'base',
            'amount' => 4666.67,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'plan_id' => $newPlan->id,
            'type' => 'base',
            'amount' => 10666.67,
        ]);
    }
}
