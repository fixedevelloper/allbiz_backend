<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{

    protected $fillable=[
    'amount','user_id','status'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function commissions() { return $this->hasMany(Commission::class); }
}
