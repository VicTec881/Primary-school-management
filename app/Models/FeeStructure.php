<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    protected $fillable = [
        'academic_year_id',
        'term_id',
        'grade_id',
        'title',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class);
    }

    public function mandatoryItems(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class)->where('is_optional', false);
    }

    public function optionalItems(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class)->where('is_optional', true);
    }

    public function getTotalMandatoryAmountAttribute(): float
    {
        return (float) $this->mandatoryItems()->sum('amount');
    }
}

