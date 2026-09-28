<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class UsageEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'merchant_id',
        'customer_id',
        'idempotency_key',
        'units',
        'usage_date',
    ];

    protected $casts = [
        'units' => 'integer',
        'usage_date' => 'date',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
