<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QrTicket extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'payload_signed',
        'status_qr',
        'regenerated_from_qr_id',
        'waktu_terbit',
        'waktu_expired',
    ];

    protected $casts = [
        'waktu_terbit' => 'datetime',
        'waktu_expired' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // QR sebelumnya yang digantikan oleh QR ini (BR-7)
    public function regeneratedFrom(): BelongsTo
    {
        return $this->belongsTo(QrTicket::class, 'regenerated_from_qr_id');
    }

    // QR pengganti dari QR ini: paling banyak satu (ERD 0..1, dijaga uq_qr_regenerated_from)
    public function regeneratedTo(): HasOne
    {
        return $this->hasOne(QrTicket::class, 'regenerated_from_qr_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'qr_ticket_id');
    }
}