<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_can_be_recorded(): void
    {
        $merchant = Merchant::factory()->create();

        $customer = Customer::factory()->create([
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->postJson('/api/usage', [
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'idempotency_key' => 'usage-001',
            'units' => 100,
            'usage_date' => now()->toDateString(),
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.units', 100);
    }

    public function test_duplicate_idempotency_key_does_not_create_duplicate_usage(): void
    {
        $merchant = Merchant::factory()->create();

        $customer = Customer::factory()->create([
            'merchant_id' => $merchant->id,
        ]);

        $payload = [
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'idempotency_key' => 'usage-001',
            'units' => 100,
            'usage_date' => now()->toDateString(),
        ];

        $this->postJson('/api/usage', $payload)
            ->assertStatus(201);

        $this->postJson('/api/usage', $payload)
            ->assertStatus(200)
            ->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('usage_events', 1);
    }
}
