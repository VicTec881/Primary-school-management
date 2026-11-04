<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_recurring',
        'is_optional',
    ];

    protected function casts(): array
    {
        return [
            'is_recurring' => 'boolean',
            'is_optional' => 'boolean',
        ];
    }

    public function structureItems(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class);
    }
}

