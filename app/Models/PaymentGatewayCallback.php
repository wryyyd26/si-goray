<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayCallback extends Model
{
    use HasFactory, HasUuids;

    // Primary key menggunakan UUID
    protected $keyType = 'string';
    public $incrementing = false;

    // Tabel hanya memiliki received_at,
    // bukan created_at dan updated_at
    public $timestamps = false;

    // Kolom yang boleh diisi melalui create()
    protected $fillable = [
        'transaction_id',
        'status_callback',
        'signature_valid',
        'raw_payload',
        'received_at',
    ];

    // Konversi tipe data otomatis
    protected $casts = [
        'signature_valid' => 'boolean',
        'raw_payload' => 'array',
        'received_at' => 'datetime',
    ];

    // Relasi callback dengan transaksi pembayaran
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            PaymentTransaction::class,
            'transaction_id'
        );
    }
}
