<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'status',
        'reference',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
