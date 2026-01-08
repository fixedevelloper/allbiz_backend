<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawAccount extends Model
{
    use HasFactory;

    protected $table = 'withdraw_accounts'; // Nom de la table

    protected $fillable = [
        'name',
        'phone',
        'account_number',
        'operator_id',
        'user_id',
    ];

    /**
     * L'utilisateur propriétaire du compte de retrait
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'opérateur associé au compte de retrait
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
