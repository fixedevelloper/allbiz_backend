<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'image_url',
        'user_type',
        'password',
        'referrer_id',
        'balance',
        'membership_level','city','permanentaddress','presentaddress','dob','country_code'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function investments() { return $this->hasMany(Investment::class); }
    public function commissions() { return $this->hasMany(Commission::class, 'referrer_id'); }
    public function referrer() { return $this->belongsTo(User::class, 'referrer_id'); }
    public function referrals() { return $this->hasMany(User::class, 'referrer_id'); }
    public function roulettes()
    {
        return $this->hasManyThrough(
            Roulette::class,
            Commission::class,
            'referrer_id',      // FK sur commissions
            'commission_id',    // FK sur roulettes
            'id',
            'id'
        );
    }

    public function investment()
    {
        return $this->hasOne(Investment::class);
    }
  // Transactions du wallet
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function referralLink(): string
    {
        return config('app.frontend_url') . '/auth/signup?ref=' . $this->id;
    }

}
