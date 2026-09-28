<?php

namespace App\Jobs;

use App\Models\DailyUsageAggregate;
use App\Models\UsageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateDailyUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(private readonly string $usageDate) {}

    public function handle(): void
    {
        UsageEvent::query()
            ->select([
                'merchant_id',
                'customer_id',
                'usage_date',
                DB::raw('SUM(units) as total_units'),
            ])
            ->whereDate('usage_date', $this->usageDate)
            ->groupBy(
                'merchant_id',
                'customer_id',
                'usage_date'
            )
            ->orderBy('customer_id')
            ->chunk(
                500,
                function ($usageGroups) {
                    $aggregates = $usageGroups->map(function ($usageGroup) {
                        return [
                            'merchant_id' => $usageGroup->merchant_id,
                            'customer_id' => $usageGroup->customer_id,
                            'usage_date' => $usageGroup->usage_date,
                            'total_units' => $usageGroup->total_units,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                    DailyUsageAggregate::upsert(
                        $aggregates,
                        ['customer_id', 'usage_date'],
                        ['merchant_id', 'total_units', 'updated_at']
                    );
                }
            );
    }
}
