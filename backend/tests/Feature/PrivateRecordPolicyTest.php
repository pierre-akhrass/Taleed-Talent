<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformRoleAssignment;
use App\Models\User;
use App\Models\WellbeingEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateRecordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_records_are_owner_only_even_for_application_admins(): void
    {
        $organization = Organization::create(['name' => 'Private Policy Organization']);
        $owner = User::factory()->create();
        $otherLeader = User::factory()->create();
        $admin = User::factory()->create();
        $this->addMembership($organization, $owner);
        $this->addMembership($organization, $otherLeader);
        PlatformRoleAssignment::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'assigned_at' => now(),
        ]);

        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $owner->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'focus_theme' => 'care',
            'status' => 'active',
        ]);
        $conversation = Conversation::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $owner->id,
            'month' => '2026-10-01',
            'status' => 'draft',
            'guide_version_id' => 'guide-v1',
            'encrypted_payload' => 'synthetic-ciphertext',
            'key_version' => 'test-key-v1',
        ]);
        $wellbeingEntry = WellbeingEntry::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $owner->id,
            'month' => '2026-10-01',
            'status' => 'draft',
            'rules_version_id' => 'numeric-only-v1',
            'encrypted_payload' => 'synthetic-ciphertext',
            'key_version' => 'test-key-v1',
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $plan));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $conversation));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $wellbeingEntry));
        $this->assertFalse(Gate::forUser($otherLeader)->allows('view', $conversation));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $conversation));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $wellbeingEntry));
        $this->assertArrayNotHasKey('encrypted_payload', $conversation->toArray());
        $this->assertArrayNotHasKey('encrypted_payload', $wellbeingEntry->toArray());
    }

    public function test_owner_loses_private_access_when_membership_is_revoked(): void
    {
        $organization = Organization::create(['name' => 'Revoked Membership Organization']);
        $owner = User::factory()->create();
        $this->addMembership($organization, $owner);
        $conversation = Conversation::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $owner->id,
            'month' => '2026-10-01',
            'status' => 'draft',
            'guide_version_id' => 'guide-v1',
            'encrypted_payload' => 'synthetic-ciphertext',
            'key_version' => 'test-key-v1',
        ]);
        DB::table('organization_memberships')
            ->where('organization_id', $organization->id)
            ->where('user_id', $owner->id)
            ->update(['status' => 'revoked', 'revoked_at' => now()]);

        $this->assertFalse(Gate::forUser($owner)->allows('view', $conversation));
    }

    private function addMembership(Organization $organization, User $user): void
    {
        DB::table('organization_memberships')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'leader',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
