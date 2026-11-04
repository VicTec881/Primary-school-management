<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerEnrollment extends Model
{
    protected $fillable = [
        'learner_id',
        'academic_year_id',
        'grade_id',
        'stream_id',
        'roll_number',
        'status',
        'date_enrolled',
    ];

    protected function casts(): array
    {
        return [
            'date_enrolled' => 'date',
        ];
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }
}

