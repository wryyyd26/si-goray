<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetugasGerbang extends Model
{
    use HasFactory, HasUuids;


    protected $keyType = 'string';

    public $incrementing = false;


    public $timestamps = false;


    /**
     * Nama tabel database.
     */
    protected $table = 'petugas_gerbang';


    protected $fillable = [
    'user_id',
    'nama',
    'username',
    'no_hp',
    'aktif',
];

    protected $casts = [
        'aktif' => 'boolean',
    ];


    /**
     * Riwayat scan yang dilakukan petugas.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(
            AccessLog::class,
            'petugas_id'
        );
    }

    /**
 * Akun yang digunakan petugas untuk autentikasi.
 */
public function user(): BelongsTo
{
    return $this->belongsTo(
        User::class,
        'user_id'
    );
}
}