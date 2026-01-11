<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'downloadable',
        'is_promotion',
        'price',
        'promotion_price',
        'category_id',
        'description',
        'how_it_works',
    ];

    protected $casts = [
        'downloadable' => 'boolean',
        'is_promotion' => 'boolean',
        'price' => 'decimal:2',
        'promotion_price' => 'decimal:2',
    ];

    /** Relations */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Image::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Helpers */

    public function getFinalPriceAttribute(): float
    {
        return $this->is_promotion && $this->promotion_price
            ? (float) $this->promotion_price
            : (float) $this->price;
    }
}
