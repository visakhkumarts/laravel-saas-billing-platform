<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'merchant_id',
        'name',
        'base_price',
        'billing_cycle',
        'included_units',
        'overage_rate',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'overage_rate' => 'decimal:6',
        'included_units' => 'integer',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function incomingSubscriptionChanges(): HasMany
    {
        return $this->hasMany(
            SubscriptionChange::class,
            'to_plan_id'
        );
    }

    public function outgoingSubscriptionChanges(): HasMany
    {
        return $this->hasMany(
            SubscriptionChange::class,
            'from_plan_id'
        );
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
