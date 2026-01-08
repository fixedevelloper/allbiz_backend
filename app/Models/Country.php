<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'iso',
        'code',
        'status',
        'image_url',
    ];

    /**
     * Récupère tous les opérateurs associés à ce pays
     */
    public function operators()
    {
        return $this->hasMany(Operator::class);
    }
}
