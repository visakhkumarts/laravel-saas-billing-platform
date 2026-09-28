<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private readonly BillingService $billingService) {}

    public function generateInvoice(Subscription $subscription,Carbon $periodStart,Carbon $periodEnd    ): Invoice {

        $amounts = $this->billingService->calculateTotal($subscription,$periodStart,$periodEnd);

        return DB::transaction(function () use ($subscription,$periodStart,$periodEnd,$amounts) {
            
            $invoice = Invoice::firstOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'billing_period_start' => $periodStart,
                    'billing_period_end' => $periodEnd,
                ],
                [
                    'merchant_id' => $subscription->customer->merchant_id,
                    'customer_id' => $subscription->customer_id,
                    'base_amount' => $amounts['base_amount'],
                    'overage_amount' => $amounts['overage_amount'],
                    'total_amount' => $amounts['total_amount'],
                    'status' => 'issued',
                    'issued_at' => now(),
                ]
            );

            if ($invoice->wasRecentlyCreated) {
                foreach ($amounts['base_items'] as $baseItem) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'plan_id' => $baseItem['plan_id'],
                        'type' => 'base',
                        'description' => 'Subscription base charge',
                        'quantity' => 1,
                        'unit_price' => $baseItem['amount'],
                        'amount' => $baseItem['amount'],
                    ]);
                }

                foreach ($amounts['overage_items'] as $overageItem) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'plan_id' => $overageItem['plan_id'],
                        'type' => 'overage',
                        'description' => 'Usage overage charge',
                        'quantity' => 1,
                        'unit_price' => $overageItem['amount'],
                        'amount' => $overageItem['amount'],
                    ]);
                }
            }

            return $invoice;
        });
    }
}
