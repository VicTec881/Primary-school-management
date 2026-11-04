<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScale extends Model
{
    protected $fillable = [
        'name',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(GradingScaleDetail::class)->orderByDesc('level');
    }

    public function getLevelForScore(float $score): ?GradingScaleDetail
    {
        return $this->details()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }
}

