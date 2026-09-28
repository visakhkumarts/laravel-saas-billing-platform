<?php

namespace Tests\Unit;

use App\Jobs\AggregateDailyUsageJob;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AggregateDailyUsageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_usage_is_aggregated_for_a_customer(): void
    {
        $merchant = Merchant::factory()->create();

        $customer = Customer::factory()->create([
            'merchant_id' => $merchant->id,
        ]);

        $usageDate = now()->toDateString();

        UsageEvent::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'units' => 100,
            'usage_date' => $usageDate,
            'idempotency_key' => 'usage-001',
        ]);

        UsageEvent::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'units' => 250,
            'usage_date' => $usageDate,
            'idempotency_key' => 'usage-002',
        ]);

        $job = new AggregateDailyUsageJob($usageDate);

        $job->handle();

        $this->assertDatabaseHas('daily_usage_aggregates', [
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => $usageDate . ' 00:00:00',
            'total_units' => 350,
        ]);
    }
}
