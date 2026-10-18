<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Outbreak;
use App\Models\User;

class OutbreakPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function close(User $user, Outbreak $outbreak): bool
    {
        if ($user->role === UserRole::Farmer) {
            return false;
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return $user->role !== UserRole::Farmer;
    }
}
