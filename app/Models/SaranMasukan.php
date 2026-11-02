<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SaranMasukan extends Model
{
    use HasUuids;

    protected $table = 'saran_masukan';

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'category',
        'subject',
        'message',
        'status',
    ];
}
