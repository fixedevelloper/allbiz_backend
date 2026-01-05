<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Roulette extends Model
{
    protected $fillable=[
        'commission_id','amount','status','executed_at','type'
    ];

    public function commission() { return $this->belongsTo(Commission::class); }
}
