<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Identity extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'nik_enc',
        'nik_hash',
        'tanggal_lahir_enc',
        'status_warga_kota',
        'sumber_validasi',
        'divalidasi_at',
    ];

    /**
     * PII tidak boleh bocor melalui response JSON.
     */
    protected $hidden = [
        'nik_enc',
        'nik_hash',
        'tanggal_lahir_enc',
    ];

    protected $casts = [
        /*
         * Laravel mengenkripsi data saat disimpan
         * dan mendekripsinya saat dibaca.
         */
        'nik_enc' => 'encrypted',
        'tanggal_lahir_enc' => 'encrypted',

        'status_warga_kota' => 'boolean',
        'divalidasi_at' => 'datetime',
    ];

    /**
     * Relasi ke user pemilik identity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Satu identity dapat digunakan oleh beberapa booking.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}