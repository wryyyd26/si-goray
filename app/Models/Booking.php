<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'identity_id',
        'jenis_booking',
        'regular_slot_id',
        'event_schedule_id',
        'tariff_id',
        'retribusi_diperlukan',
        'status_booking',
    ];

    protected $casts = [
        'retribusi_diperlukan' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }

    public function regularSlot(): BelongsTo
    {
        return $this->belongsTo(RegularSlot::class);
    }

    public function eventSchedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(
            TariffReference::class,
            'tariff_id'
        );
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function pendingPayment(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class)
            ->where('status_pembayaran', 'PENDING');
    }

    public function qrTickets(): HasMany
    {
        return $this->hasMany(QrTicket::class);
    }

    public function activeQrTicket(): HasOne
    {
        return $this->hasOne(QrTicket::class)
            ->where('status_qr', 'AKTIF');
    }
}
