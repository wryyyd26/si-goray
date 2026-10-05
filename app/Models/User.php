<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'no_hp_email',
        'status_otp',
    ];

    protected function casts(): array
    {
        return [
            'status_otp' => 'boolean',
        ];
    }

    public function getRememberTokenName()
    {
        return '';
    }

    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OtpVerification::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(Identity::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}