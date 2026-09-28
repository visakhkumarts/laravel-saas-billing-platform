<?php

namespace App\Console\Commands;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'billing:generate-invoices';

    protected $description = 'Generate invoices for subscriptions at the end of the billing cycle';

    public function handle(): int
    {
        Subscription::query()
            ->where('status', 'active')
            // ->where('current_period_end', '<=', now())
            ->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    GenerateInvoiceJob::dispatch(
                        $subscription,
                        Carbon::parse($subscription->current_period_start),
                        Carbon::parse($subscription->current_period_end)
                    );
                }
            });

        $this->info('Invoice generation jobs dispatched.');

        return self::SUCCESS;
    }
}
