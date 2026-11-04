<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerFeeAccount extends Model
{
    protected $fillable = [
        'learner_id',
        'opening_balance',
        'total_billed',
        'total_paid',
        'total_discounted',
        'current_balance',
        'credit_balance',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'total_billed' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'total_discounted' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'credit_balance' => 'decimal:2',
        ];
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }
}

