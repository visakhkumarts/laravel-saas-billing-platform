<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\DailyUsageAggregate;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_top_five_customers(): void
    {
        $merchant = Merchant::factory()->create();

        for ($i = 1; $i <= 6; $i++) {
            $customer = Customer::factory()->create([
                'merchant_id' => $merchant->id,
            ]);

            DailyUsageAggregate::factory()->create([
                'merchant_id' => $merchant->id,
                'customer_id' => $customer->id,
                'usage_date' => now()->toDateString(),
                'total_units' => $i * 1000,
            ]);
        }

        $result = app(DashboardService::class)
            ->getDashboard($merchant);

        $this->assertCount(5, $result['top_customers']);

        $this->assertEquals(
            6000,
            $result['top_customers']->first()->total_units
        );
    }

    public function test_dashboard_detects_usage_drop_over_fifty_percent(): void
    {
        $merchant = Merchant::factory()->create();

        $customer = Customer::factory()->create([
            'merchant_id' => $merchant->id,
        ]);

        DailyUsageAggregate::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => now()->subMonth()->startOfMonth()->addDays(5),
            'total_units' => 10000,
        ]);

        DailyUsageAggregate::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => now()->startOfMonth()->addDays(5),
            'total_units' => 4000,
        ]);

        $result = app(DashboardService::class)
            ->getDashboard($merchant);

        $this->assertCount(1, $result['usage_drop_customers']);

        $this->assertEquals(
            60,
            $result['usage_drop_customers'][0]['drop_percentage']
        );
    }
}
