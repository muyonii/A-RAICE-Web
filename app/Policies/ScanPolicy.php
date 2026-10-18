<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Scan;
use App\Models\User;

class ScanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Scan $scan): bool
    {
        if ($user->role === UserRole::Farmer) {
            $profileId = $user->farmerProfile?->id;

            return $profileId !== null && $scan->farmer_id === $profileId;
        }

        return $user->role === UserRole::LguStaff;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
