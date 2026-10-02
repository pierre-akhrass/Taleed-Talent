<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlanPolicy
{
    public function view(User $user, Plan $plan): bool
    {
        return $this->isOwnerWithActiveMembership($user, $plan);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $plan->status === 'active' && $this->isOwnerWithActiveMembership($user, $plan);
    }

    public function close(User $user, Plan $plan): bool
    {
        return $plan->status === 'active' && $this->isOwnerWithActiveMembership($user, $plan);
    }

    public function reopen(User $user, Plan $plan): bool
    {
        return $plan->status === 'closed' && $this->isOwnerWithActiveMembership($user, $plan);
    }

    private function isOwnerWithActiveMembership(User $user, Plan $plan): bool
    {
        return (int) $plan->owner_user_id === (int) $user->id
            && DB::table('organization_memberships')
                ->where('organization_id', $plan->organization_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->exists();
    }
}
