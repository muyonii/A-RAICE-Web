<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->role === UserRole::Farmer) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        if ($user->role === UserRole::Farmer) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->role === UserRole::Farmer) {
            return false;
        }

        return true;
    }
}
