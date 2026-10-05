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

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nik_enc',
        'nik_hash',
        'tanggal_lahir_enc',
        'status_warga_kota',
        'sumber_validasi',
        'divalidasi_at',
    ];

    // PII tidak boleh bocor lewat response JSON (PRD §8.2)
    protected $hidden = [
        'nik_enc',
        'nik_hash',
        'tanggal_lahir_enc',
    ];

    protected $casts = [
        // Laravel mengenkripsi saat simpan dan mendekripsi saat baca secara otomatis.
        // Jadi $identity->nik_enc mengembalikan NIK asli, sedangkan di database tersimpan terenkripsi.
        // PENTING: jangan ganti APP_KEY setelah ada data, karena data lama tidak bisa dibuka lagi.
        'nik_enc' => 'encrypted',
        'tanggal_lahir_enc' => 'encrypted',
        'status_warga_kota' => 'boolean',
        'divalidasi_at' => 'datetime',
    ];

    // nik_hash diisi oleh service, bukan model:
    // hash_hmac('sha256', $nik, <secret khusus>)  -> 64 karakter, cocok dengan VARCHAR(64)

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}