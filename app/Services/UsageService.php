<?php

namespace App\Services;

use App\Models\UsageEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class UsageService
{
    public function record(array $data): array
    {
        // Make sure the customer belongs to the merchant.
        $customerBelongsToMerchant = DB::table('customers')
            ->where('id', $data['customer_id'])
            ->where('merchant_id', $data['merchant_id'])
            ->exists();

        if (!$customerBelongsToMerchant) {
            throw new \InvalidArgumentException(
                'Customer does not belong to the specified merchant.'
            );
        }

        try {
            $usageEvent = UsageEvent::create($data);

            return [
                'event' => $usageEvent,
                'duplicate' => false,
            ];
        } catch (QueryException $exception) {
            // The unique index on merchant_id + idempotency_key
            // protects against duplicate requests.
            $existingEvent = UsageEvent::query()
                ->where('merchant_id', $data['merchant_id'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existingEvent) {
                return [
                    'event' => $existingEvent,
                    'duplicate' => true,
                ];
            }

            throw $exception;
        }
    }
}
