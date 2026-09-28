<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionService
{
    public function changePlan(Subscription $subscription, Plan $newPlan, Carbon $effectiveAt): SubscriptionChange
    {
        return DB::transaction(function () use ($subscription, $newPlan, $effectiveAt) {

            $subscription->load('customer.merchant');

            // Make sure the new plan belongs to the same merchant.
            if ($newPlan->merchant_id !== $subscription->customer->merchant_id) {
                throw new InvalidArgumentException(
                    'The new plan does not belong to the customer merchant.'
                );
            }

            // Effective date must be inside the current billing period.
            if ($effectiveAt->isBefore($subscription->current_period_start) || !$effectiveAt->isBefore($subscription->current_period_end)) {
                throw new InvalidArgumentException(
                    'The effective date must be within the current billing period.'
                );
            }

            // No need to create a change if the plan is unchanged.
            if ($subscription->plan_id === $newPlan->id) {
                throw new InvalidArgumentException(
                    'Customer is already subscribed to this plan.'
                );
            }

            $change = SubscriptionChange::create([
                'subscription_id' => $subscription->id,
                'from_plan_id' => $subscription->plan_id,
                'to_plan_id' => $newPlan->id,
                'effective_at' => $effectiveAt,
            ]);

            $subscription->update([
                'plan_id' => $newPlan->id,
            ]);

            return $change;
        });
    }
}
