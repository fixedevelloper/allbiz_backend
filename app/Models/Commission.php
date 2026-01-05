<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'referrer_id', 'investment_id', 'roulette_count', 'amount'
    ];


    public function referrer() { return $this->belongsTo(User::class, 'referrer_id'); }
    public function investment() { return $this->belongsTo(Investment::class); }
    public function roulettes() { return $this->hasMany(Roulette::class); }
}
