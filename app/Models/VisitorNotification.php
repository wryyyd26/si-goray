<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VisitorNotification extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'event_key', 'category', 'title', 'message', 'icon', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
