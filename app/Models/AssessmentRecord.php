<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRecord extends Model
{
    protected $fillable = [
        'learner_id',
        'academic_year_id',
        'term_id',
        'stream_id',
        'learning_area_id',
        'assessment_type_id',
        'raw_score',
        'max_score',
        'performance_level',
        'rubric_comments',
        'teacher_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:2',
            'max_score' => 'decimal:2',
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

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function learningArea(): BelongsTo
    {
        return $this->belongsTo(LearningArea::class);
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function getPercentageAttribute(): float
    {
        if (!$this->max_score || (float)$this->max_score == 0) {
            return 0;
        }
        return round(((float)$this->raw_score / (float)$this->max_score) * 100, 2);
    }
}

