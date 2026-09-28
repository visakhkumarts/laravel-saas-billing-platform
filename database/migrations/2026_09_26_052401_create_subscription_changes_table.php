<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_changes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnDelete();

            $table->foreignId('from_plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            $table->foreignId('to_plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            $table->timestamp('effective_at');

            $table->timestamps();

            $table->index(['subscription_id', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_changes');
    }
};
