<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class InvitationPolicy
{
    public function createInvitation(User $user, Organization $organization): bool
    {
        return $user->isApplicationAdmin() && $organization->status === 'active';
    }
}