<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\UsageEvent;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_change_creates_two_billing_segments(): void
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
            'effective_at' => Carbon::parse('2026-09-15'),
        ]);

        $billing = app(BillingService::class);

        $segments = $billing->getPlanSegments(
            $subscription,
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-10-01')
        );

        $this->assertCount(2, $segments);

        $this->assertEquals(
            $oldPlan->id,
            $segments[0]['plan']['id']
        );

        $this->assertEquals(
            $newPlan->id,
            $segments[1]['plan']['id']
        );
    }

    public function test_base_charge_is_prorated_when_plan_changes(): void
    {
        $merchant = Merchant::factory()->create();

        $oldPlan = Plan::factory()->create([
            'merchant_id' => $merchant->id,
            'base_price' => 10000,
        ]);

        $newPlan = Plan::factory()->create([
            'merchant_id' => $merchant->id,
            'base_price' => 20000,
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
            'effective_at' => Carbon::parse('2026-09-15'),
        ]);

        $billing = app(BillingService::class);

        $result = $billing->calculateTotal(
            $subscription,
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-10-01')
        );

        $this->assertCount(2, $result['base_items']);

        $this->assertEquals(4666.67, $result['base_items'][0]['amount']);
        $this->assertEquals(10666.67, $result['base_items'][1]['amount']);

        $this->assertEquals(15333.34, $result['base_amount']);
    }

    public function test_overage_is_calculated_when_usage_exceeds_prorated_included_units(): void
    {
        $merchant = Merchant::factory()->create();

        $plan = Plan::factory()->create([
            'merchant_id' => $merchant->id,
            'base_price' => 10000,
            'included_units' => 100000,
            'overage_rate' => 0.10,
        ]);

        $customer = Customer::factory()->create([
            'merchant_id' => $merchant->id,
        ]);

        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
        ]);

        UsageEvent::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'units' => 60000,
            'usage_date' => '2026-09-10',
            'idempotency_key' => 'test-overage-001',
        ]);

        $billing = app(BillingService::class);

        $result = $billing->calculateTotal(
            $subscription,
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-10-01')
        );

        $this->assertEquals(0, $result['overage_amount']);
    }
}
