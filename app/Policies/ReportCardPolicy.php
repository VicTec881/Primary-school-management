<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ReportCard;

class ReportCardPolicy
{
    public function view(User $user, ReportCard $reportCard): bool
    {
        if ($user->hasRole(['super_admin', 'headteacher', 'teacher', 'registrar'])) {
            return true;
        }

        // Parent can ONLY view linked child's report card
        if ($user->hasRole('parent') && $user->guardian) {
            return $reportCard->learner->guardians()
                ->where('guardian_id', $user->guardian->id)
                ->exists();
        }

        return false;
    }

    public function update(User $user, ReportCard $reportCard): bool
    {
        return $user->hasRole(['super_admin', 'headteacher', 'teacher']);
    }

    public function approve(User $user, ReportCard $reportCard): bool
    {
        return $user->hasRole(['super_admin', 'headteacher']);
    }
}

