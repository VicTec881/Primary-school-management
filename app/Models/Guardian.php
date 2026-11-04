<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'national_id',
        'phone',
        'email',
        'occupation',
        'address',
        'emergency_priority',
    ];

    protected function casts(): array
    {
        return [
            'emergency_priority' => 'integer',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(Learner::class, 'guardian_learner')
            ->withPivot('relationship', 'is_primary', 'authorized_to_collect')
            ->withTimestamps();
    }
}

