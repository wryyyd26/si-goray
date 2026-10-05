<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PetugasGerbang extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    // Nama tabel aslinya tanpa 's' di akhir
    protected $table = 'petugas_gerbang';

    protected $fillable = [
        'nama',
        'username',
        'no_hp',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'petugas_id');
    }
}