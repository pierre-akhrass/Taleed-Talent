<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WellbeingEntry;
use Illuminate\Support\Facades\DB;

class WellbeingEntryPolicy
{
    public function view(User $user, WellbeingEntry $entry): bool
    {
        return $this->isOwnerWithActiveMembership($user, $entry);
    }

    public function update(User $user, WellbeingEntry $entry): bool
    {
        return $this->isOwnerWithActiveMembership($user, $entry);
    }

    public function delete(User $user, WellbeingEntry $entry): bool
    {
        return $this->isOwnerWithActiveMembership($user, $entry);
    }

    private function isOwnerWithActiveMembership(User $user, WellbeingEntry $entry): bool
    {
        return (int) $entry->owner_user_id === (int) $user->id
            && DB::table('organization_memberships')
                ->where('organization_id', $entry->organization_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->exists();
    }
}
