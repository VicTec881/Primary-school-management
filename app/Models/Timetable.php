<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timetable extends Model
{
    protected $fillable = [
        'stream_id',
        'learning_area_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room_number',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function learningArea(): BelongsTo
    {
        return $this->belongsTo(LearningArea::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}

