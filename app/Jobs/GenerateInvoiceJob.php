<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public Carbon $periodStart,
        public Carbon $periodEnd
    ) {}

    public function handle(InvoiceService $invoiceService): void
    {
        $invoiceService->generateInvoice(
            $this->subscription,
            $this->periodStart,
            $this->periodEnd
        );
    }
}
