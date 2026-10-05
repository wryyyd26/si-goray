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

    // BR-1: hanya salah satu dari regular_slot_id ATAU event_schedule_id yang terisi,
    // sesuai jenis_booking. Dijaga CHECK constraint di DB; validasi juga di FormRequest/service.

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
        return $this->belongsTo(TariffReference::class, 'tariff_id');
    }

    // Seluruh riwayat transaksi (bisa lebih dari satu akibat retry, BR-4)
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // Transaksi yang sedang menunggu bayar: maksimal 1 (dijaga uq_payment_pending_per_booking)
    public function pendingPayment(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class)
            ->where('status_pembayaran', 'PENDING');
    }

    // Seluruh QR termasuk yang sudah INVALID/SUDAH_DIPAKAI (rantai regenerasi, BR-7)
    public function qrTickets(): HasMany
    {
        return $this->hasMany(QrTicket::class);
    }

    // QR yang sedang berlaku: maksimal 1 (dijaga uq_qr_aktif_per_booking, BR-5)
    public function activeQrTicket(): HasOne
    {
        return $this->hasOne(QrTicket::class)
            ->where('status_qr', 'AKTIF');
    }
}