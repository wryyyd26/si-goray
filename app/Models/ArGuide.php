<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArGuide extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'model_path',
        'thumbnail_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ArGuideStep::class)
            ->orderBy('step_order');
    }
}