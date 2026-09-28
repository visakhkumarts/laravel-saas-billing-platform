<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained('merchants')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->string('idempotency_key', 100);
            $table->unsignedBigInteger('units');
            $table->date('usage_date');

            $table->timestamps();

            $table->unique(['merchant_id', 'idempotency_key']);

            $table->index(['customer_id', 'usage_date']);
            $table->index(['merchant_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
