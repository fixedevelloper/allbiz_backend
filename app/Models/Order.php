<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'amount',
        'amount_rest',
        'operator',
        'reference_id',
        'confirmed_at',
        'status',
        'meta',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_rest' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'meta' => 'array',
    ];

    /** Relations */

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Helpers */

    public function isPaid(): bool
    {
        return $this->status === 'confirmed';
    }
}
