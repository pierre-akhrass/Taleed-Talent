<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function create(User $user): bool
    {
        return $user->isApplicationAdmin();
    }
}