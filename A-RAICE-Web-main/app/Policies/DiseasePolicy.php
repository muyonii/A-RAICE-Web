<?php

namespace App\Policies;

use App\Models\Disease;
use App\Models\User;

class DiseasePolicy
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
    public function update(User $user, Disease $disease): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Disease $disease): bool
    {
        return false;
    }
}
