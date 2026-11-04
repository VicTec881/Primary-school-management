<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningArea extends Model
{
    protected $fillable = [
        'name',
        'code',
        'stage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AssessmentRecord::class);
    }
}

