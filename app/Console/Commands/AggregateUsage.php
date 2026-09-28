<?php

namespace App\Console\Commands;

use App\Jobs\AggregateDailyUsageJob;
use Illuminate\Console\Command;

class AggregateUsage extends Command
{
    protected $signature = 'usage:aggregate';

    protected $description = 'Dispatch daily usage aggregation job';

    public function handle(): int
    {
        AggregateDailyUsageJob::dispatch(
            now()->toDateString()
        );

        $this->info('Usage aggregation job dispatched.');

        return self::SUCCESS;
    }
}