<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class PhaseTwoSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_contract_references_resolve(): void
    {
        $document = Yaml::parseFile(dirname(base_path()).'/contracts/openapi.yaml');
        $walk = function (array $node) use (&$walk, $document): void {
            foreach ($node as $key => $value) {
                if ($key === '$ref') {
                    $target = $document;
                    foreach (explode('/', substr($value, 2)) as $part) {
                        $part = str_replace(['~1', '~0'], ['/', '~'], $part);
                        self::assertIsArray($target);
                        self::assertArrayHasKey($part, $target, 'Unresolved OpenAPI reference: '.$value);
                        $target = $target[$part];
                    }
                } elseif (is_array($value)) {
                    $walk($value);
                }
            }
        };

        $walk($document);
        $this->assertNotEmpty($document['paths']);
    }

    public function test_plan_owner_must_be_a_member_and_has_one_plan_per_month(): void
    {
        $organization = Organization::create(['name' => 'Schema Test Organization']);
        $otherOrganization = Organization::create(['name' => 'Other Schema Test Organization']);
        $owner = User::factory()->create();
        $otherMember = User::factory()->create();
        $this->addMembership($organization, $owner);
        $this->addMembership($organization, $otherMember);

        $planId = $this->insertPlan($organization, $owner);

        try {
            $this->insertPlan($organization, $owner);
            self::fail('A second plan for the same owner and month should be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('plans', 1);
        }

        try {
            $this->insertPlan($otherOrganization, $owner);
            self::fail('A plan owner without membership in the organization should be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseHas('plans', ['id' => $planId]);
            $this->assertDatabaseCount('plans', 1);
        }

        try {
            DB::table('plan_closures')->insert([
                'id' => (string) Str::ulid(),
                'organization_id' => $organization->id,
                'owner_user_id' => $otherMember->id,
                'plan_id' => $planId,
                'revision_number' => 1,
                'closed_at' => now(),
                'public_integrity_hash' => str_repeat('b', 64),
                'safe_facts_json' => '{}',
                'pinned_version_ids_json' => '[]',
                'metrics_definition_version' => 'metrics-v1',
            ]);
            self::fail('A closure owner must match the parent plan owner.');
        } catch (QueryException) {
            $this->assertDatabaseCount('plan_closures', 0);
        }
    }

    public function test_occurrence_date_slot_rejects_collision_but_can_be_reused_after_cancellation(): void
    {
        $organization = Organization::create(['name' => 'Occurrence Schema Organization']);
        $owner = User::factory()->create();
        $otherMember = User::factory()->create();
        $this->addMembership($organization, $owner);
        $this->addMembership($organization, $otherMember);
        $planId = $this->insertPlan($organization, $owner);

        $activityId = (string) Str::ulid();
        $activityVersionId = (string) Str::ulid();
        DB::table('activities')->insert([
            'id' => $activityId,
            'stable_activity_key' => 'schema-test-activity',
            'kind' => 'source',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('activity_versions')->insert([
            'id' => $activityVersionId,
            'activity_id' => $activityId,
            'version_number' => 1,
            'theme' => 'care',
            'scope' => 'individual',
            'adapted_title' => 'Test activity',
            'description' => 'Synthetic schema fixture.',
            'content_hash' => str_repeat('a', 64),
            'created_at' => now(),
        ]);
        DB::table('activities')->where('id', $activityId)->update(['current_version_id' => $activityVersionId]);

        $commitmentId = (string) Str::ulid();
        DB::table('plan_commitments')->insert([
            'id' => $commitmentId,
            'organization_id' => $organization->id,
            'plan_id' => $planId,
            'activity_version_id' => $activityVersionId,
            'pinned_theme' => 'care',
            'pinned_scope' => 'individual',
            'display_order' => 1,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $scheduleVersionId = (string) Str::ulid();
        DB::table('commitment_schedule_versions')->insert([
            'id' => $scheduleVersionId,
            'organization_id' => $organization->id,
            'commitment_id' => $commitmentId,
            'version_number' => 1,
            'cadence' => 'weekly',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'weekdays_json' => json_encode([1]),
            'effective_from' => '2026-10-01',
            'created_at' => now(),
        ]);

        $this->insertOccurrence($organization, $owner, $planId, $commitmentId, $scheduleVersionId, '2026-10-05', '2026-10-05', 'scheduled');

        try {
            $this->insertOccurrence($organization, $owner, $planId, $commitmentId, $scheduleVersionId, '2026-10-06', '2026-10-05', 'scheduled');
            self::fail('An active occurrence must not collide with another occurrence on the same commitment date.');
        } catch (QueryException) {
            $this->assertDatabaseCount('occurrences', 1);
        }

        $occurrenceId = DB::table('occurrences')->value('id');
        try {
            DB::table('occurrence_private_payloads')->insert([
                'occurrence_id' => $occurrenceId,
                'organization_id' => $organization->id,
                'owner_user_id' => $otherMember->id,
                'encrypted_payload' => 'synthetic-ciphertext',
                'key_version' => 'test-key-v1',
                'lock_version' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            self::fail('A private payload owner must match the parent plan owner.');
        } catch (QueryException) {
            $this->assertDatabaseCount('occurrence_private_payloads', 0);
        }

        $this->insertOccurrence($organization, $owner, $planId, $commitmentId, $scheduleVersionId, '2026-10-07', '2026-10-05', 'cancelled');
        $this->insertOccurrence($organization, $owner, $planId, $commitmentId, $scheduleVersionId, '2026-10-08', '2026-10-05', 'superseded');

        $this->assertDatabaseCount('occurrences', 3);
    }

    private function addMembership(Organization $organization, User $owner): void
    {
        DB::table('organization_memberships')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'leader',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPlan(Organization $organization, User $owner): string
    {
        $planId = (string) Str::ulid();
        DB::table('plans')->insert([
            'id' => $planId,
            'organization_id' => $organization->id,
            'owner_user_id' => $owner->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'focus_theme' => 'care',
            'status' => 'active',
            'working_revision' => 1,
            'lock_version' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $planId;
    }

    private function insertOccurrence(
        Organization $organization,
        User $owner,
        string $planId,
        string $commitmentId,
        string $scheduleVersionId,
        string $generationDate,
        string $scheduledDate,
        string $status,
    ): void {
        DB::table('occurrences')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $organization->id,
            'plan_id' => $planId,
            'commitment_id' => $commitmentId,
            'owner_user_id' => $owner->id,
            'generation_date' => $generationDate,
            'scheduled_date' => $scheduledDate,
            'schedule_version_id' => $scheduleVersionId,
            'status' => $status,
            'lock_version' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
