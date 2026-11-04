<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Learner;

class LearnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'headteacher', 'bursar', 'registrar', 'teacher']);
    }

    public function view(User $user, Learner $learner): bool
    {
        if ($user->hasRole(['super_admin', 'headteacher', 'bursar', 'registrar', 'teacher'])) {
            return true;
        }

        // Parent can only view linked learners
        if ($user->hasRole('parent') && $user->guardian) {
            return $learner->guardians()->where('guardian_id', $user->guardian->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'registrar']);
    }

    public function update(User $user, Learner $learner): bool
    {
        return $user->hasRole(['super_admin', 'registrar']);
    }

    public function delete(User $user, Learner $learner): bool
    {
        // Learners with financial or academic history cannot be deleted
        if ($learner->hasFinancialOrAcademicHistory()) {
            return false;
        }

        return $user->hasRole('super_admin');
    }
}

