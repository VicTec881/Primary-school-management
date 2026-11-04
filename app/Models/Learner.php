<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Learner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'admission_number',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'dob',
        'admission_date',
        'nemis_number',
        'assessment_number',
        'previous_school',
        'status',
        'photo_path',
        'medical_notes',
        'special_needs',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'admission_date' => 'date',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_learner')
            ->withPivot('relationship', 'is_primary', 'authorized_to_collect')
            ->withTimestamps();
    }

    public function primaryGuardian(): ?Guardian
    {
        return $this->guardians()->wherePivot('is_primary', true)->first() ?? $this->guardians()->first();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(LearnerEnrollment::class);
    }

    public function currentEnrollment(): HasOne
    {
        $currentYear = AcademicYear::current();
        return $this->hasOne(LearnerEnrollment::class)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->latestOfMany();
    }

    public function feeAccount(): HasOne
    {
        return $this->hasOne(LearnerFeeAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AssessmentRecord::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LearnerDocument::class);
    }

    public function hasFinancialOrAcademicHistory(): bool
    {
        return $this->invoices()->exists() ||
            $this->payments()->exists() ||
            $this->assessments()->exists() ||
            $this->attendanceRecords()->exists();
    }
}

