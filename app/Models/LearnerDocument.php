<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerDocument extends Model
{
    protected $fillable = [
        'learner_id',
        'title',
        'document_type',
        'file_path',
        'file_size',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }
}

