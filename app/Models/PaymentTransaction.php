<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'booking_id',
        'kode_bayar',
        'metode_pembayaran',
        'nominal',
        'status_pembayaran',
        'jumlah_retry',
        'referensi_gateway',
        'expired_at',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'expired_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(PaymentGatewayCallback::class, 'transaction_id');
    }
}