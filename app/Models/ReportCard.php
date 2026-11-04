<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCard extends Model
{
    protected $fillable = [
        'learner_id',
        'academic_year_id',
        'term_id',
        'stream_id',
        'attendance_days_present',
        'attendance_days_absent',
        'attendance_days_total',
        'class_teacher_remarks',
        'headteacher_remarks',
        'next_term_begins',
        'status',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_days_present' => 'integer',
            'attendance_days_absent' => 'integer',
            'attendance_days_total' => 'integer',
            'next_term_begins' => 'date',
            'issued_at' => 'datetime',
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

    public function getAttendancePercentageAttribute(): float
    {
        if ($this->attendance_days_total <= 0) {
            return 100.0;
        }
        return round(($this->attendance_days_present / $this->attendance_days_total) * 100, 1);
    }
}

