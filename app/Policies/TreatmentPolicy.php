<?php

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Treatment $treatment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Treatment $treatment): bool
    {
        return false;
    }
}
