<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'qr_ticket_id',
        'petugas_id',
        'waktu_scan',
        'hasil_validasi',
        'catatan',
    ];

    protected $casts = [
        'waktu_scan' => 'datetime',
    ];

    public function qrTicket(): BelongsTo
    {
        return $this->belongsTo(QrTicket::class, 'qr_ticket_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(PetugasGerbang::class, 'petugas_id');
    }
}