<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isOwnerWithActiveMembership($user, $conversation);
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->isOwnerWithActiveMembership($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $this->isOwnerWithActiveMembership($user, $conversation);
    }

    private function isOwnerWithActiveMembership(User $user, Conversation $conversation): bool
    {
        return (int) $conversation->owner_user_id === (int) $user->id
            && DB::table('organization_memberships')
                ->where('organization_id', $conversation->organization_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->exists();
    }
}
