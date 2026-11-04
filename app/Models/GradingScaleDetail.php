<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingScaleDetail extends Model
{
    protected $fillable = [
        'grading_scale_id',
        'level',
        'code',
        'name',
        'min_score',
        'max_score',
        'descriptor',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'min_score' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

    public function gradingScale(): BelongsTo
    {
        return $this->belongsTo(GradingScale::class);
    }
}

