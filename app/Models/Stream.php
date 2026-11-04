<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    protected $fillable = [
        'grade_id',
        'name',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(LearnerEnrollment::class);
    }

    public function classTeachers(): HasMany
    {
        return $this->hasMany(ClassTeacher::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->grade ? "{$this->grade->name} {$this->name}" : $this->name;
    }
}

