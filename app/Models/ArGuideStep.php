<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArGuideStep extends Model
{
    protected $fillable = [
        'ar_guide_id',
        'title',
        'instruction',
        'screen_image_path',
        'step_order',
        'animation_type',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(ArGuide::class, 'ar_guide_id');
    }
}