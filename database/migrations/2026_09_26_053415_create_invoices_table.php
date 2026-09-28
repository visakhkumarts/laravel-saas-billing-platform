<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->restrictOnDelete();

            $table->date('billing_period_start');
            $table->date('billing_period_end');

            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('overage_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->string('status', 20)->default('draft');

            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['subscription_id', 'billing_period_start', 'billing_period_end'],
                'invoice_period_unique'
            );

            $table->index([
                'merchant_id',
                'billing_period_start',
            ]);

            $table->index([
                'customer_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
