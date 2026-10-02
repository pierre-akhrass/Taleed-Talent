<?php

namespace Tests\Feature;

use App\Domain\Talent\ScheduleExpander;
use App\Models\Occurrence;
use App\Models\Organization;
use App\Models\OrganizationAnalystAssignment;
use App\Models\Plan;
use App\Models\PlanCommitment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanningApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_save_a_draft_activate_pick_three_and_reject_stale_edits(): void
    {
        $organization = Organization::create(['name' => 'Planning API Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $activities = $this->createPublishedActivities();

        $response = $this->actingAs($leader, 'app')
            ->postJson('/api/v1/plan-drafts', [
                'organizationId' => $organization->id,
                'month' => '2026-10',
                'title' => 'October growth plan',
            ]);
        $draft = $response->assertCreated()
            ->assertJsonPath('data.lockVersion', 0)
            ->json('data');

        $this->patchJson('/api/v1/plan-drafts/'.$draft['id'], [
            'expectedVersion' => 0,
            'selectedTheme' => 'care',
            'selectedActivityIds' => array_column($activities, 'id'),
            'step' => 'schedule',
        ])->assertOk()->assertJsonPath('data.lockVersion', 1);

        $this->patchJson('/api/v1/plan-drafts/'.$draft['id'], [
            'expectedVersion' => 0,
            'title' => 'Stale write',
        ])->assertConflict()->assertJsonPath('error.code', 'version_conflict');

        $plan = $this->postJson('/api/v1/plan-drafts/'.$draft['id'].'/activate', [
            'expectedVersion' => 1,
        ])->assertCreated()->assertJsonPath('data.focusTheme', 'care')->json('data');

        $this->assertDatabaseHas('plans', ['id' => $plan['id'], 'owner_user_id' => $leader->id]);
        $this->assertDatabaseCount('plan_commitments', 3);

        $commitmentId = $plan['commitments'][0]['id'];
        $schedule = $this->postJson('/api/v1/commitments/'.$commitmentId.'/schedule-versions', [
            'expectedVersion' => 0,
            'cadence' => 'weekly',
            'startDate' => '2026-10-01',
            'endDate' => '2026-10-31',
            'weekdays' => [1, 3],
        ], ['Idempotency-Key' => 'schedule-create-0001'])->assertCreated()->assertJsonPath('data.version', 1)->json('data');

        $this->postJson('/api/v1/commitments/'.$commitmentId.'/schedule-versions', [
            'expectedVersion' => 0,
            'cadence' => 'weekly',
            'startDate' => '2026-10-01',
            'endDate' => '2026-10-31',
            'weekdays' => [1, 3],
        ], ['Idempotency-Key' => 'schedule-create-0001'])->assertCreated()->assertJsonPath('data.id', $schedule['id']);
        $this->postJson('/api/v1/commitments/'.$plan['commitments'][1]['id'].'/schedule-versions', [
            'expectedVersion' => 0,
            'cadence' => 'weekly',
            'startDate' => '2026-10-01',
            'endDate' => '2026-10-31',
            'weekdays' => [1, 3],
        ], ['Idempotency-Key' => 'schedule-create-0001'])
            ->assertConflict()
            ->assertJsonPath('error.code', 'idempotency_key_reused');
        $this->postJson('/api/v1/commitments/'.$commitmentId.'/schedule-versions', [
            'expectedVersion' => 0,
            'cadence' => 'weekly',
            'startDate' => '2026-10-01',
            'endDate' => '2026-10-30',
            'weekdays' => [1, 3],
        ], ['Idempotency-Key' => 'schedule-create-0001'])->assertConflict()->assertJsonPath('error.code', 'idempotency_key_reused');

        $calendar = $this->getJson('/api/v1/calendar?organizationId='.$organization->id.'&month=2026-10')
            ->assertOk()
            ->json('data');
        $this->assertCount(8, $calendar);
        $occurrence = $calendar[0];
        $this->assertSame($occurrence['generationDate'], $occurrence['scheduledDate']);

        $this->postJson('/api/v1/occurrences/'.$occurrence['id'].'/reschedule', [
            'expectedVersion' => 0,
            'scheduledDate' => $calendar[1]['scheduledDate'],
        ], ['Idempotency-Key' => 'reschedule-collision-1'])->assertConflict()->assertJsonPath('error.code', 'occurrence_date_collision');

        $rescheduled = $this->postJson('/api/v1/occurrences/'.$occurrence['id'].'/reschedule', [
            'expectedVersion' => 0,
            'scheduledDate' => '2026-10-06',
        ], ['Idempotency-Key' => 'reschedule-success-1'])->assertOk()
            ->assertJsonPath('data.generationDate', '2026-10-05')
            ->assertJsonPath('data.scheduledDate', '2026-10-06')
            ->json('data');
        $this->postJson('/api/v1/occurrences/'.$occurrence['id'].'/reschedule', [
            'expectedVersion' => 0,
            'scheduledDate' => '2026-10-06',
        ], ['Idempotency-Key' => 'reschedule-success-1'])->assertOk()->assertJsonPath('data.id', $rescheduled['id']);
        $this->postJson('/api/v1/occurrences/'.$occurrence['id'].'/reschedule', [
            'expectedVersion' => 0,
            'scheduledDate' => '2026-10-09',
        ], ['Idempotency-Key' => 'reschedule-success-1'])->assertConflict()->assertJsonPath('error.code', 'idempotency_key_reused');

        $this->patchJson('/api/v1/occurrences/'.$occurrence['id'], [
            'expectedVersion' => 1,
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('occurrence_events', [
            'occurrence_id' => $occurrence['id'],
            'from_status' => 'scheduled',
            'to_status' => 'completed',
            'event_type' => 'status_changed',
        ]);

        $this->postJson('/api/v1/commitments/'.$commitmentId.'/schedule-versions', [
            'expectedVersion' => 3,
            'cadence' => 'weekly',
            'startDate' => '2026-10-07',
            'endDate' => '2026-10-31',
            'weekdays' => [3, 5],
        ], ['Idempotency-Key' => 'schedule-create-0002'])->assertCreated()->assertJsonPath('data.version', 2);

        $this->assertDatabaseCount('occurrences', 12);
        $this->assertDatabaseCount('occurrence_events', 9);

        $this->postJson('/api/v1/commitments/'.$commitmentId.'/schedule-versions', [
            'expectedVersion' => 3,
            'cadence' => 'weekly',
            'startDate' => '2026-10-07',
            'endDate' => '2026-10-31',
            'weekdays' => [3, 5],
        ], ['Idempotency-Key' => 'schedule-stale-0003'])->assertConflict()->assertJsonPath('error.code', 'version_conflict');
        $this->assertDatabaseCount('occurrences', 12);
        $this->assertDatabaseCount('occurrence_events', 9);

        $this->getJson('/api/v1/plans/'.$plan['id'])
            ->assertOk()
            ->assertJsonPath('data.metrics.scheduled', 9)
            ->assertJsonPath('data.metrics.completed', 1)
            ->assertJsonPath('data.metrics.eligible', 9)
            ->assertJsonPath('data.metrics.coverage.0', 'individual');

        $this->postJson('/api/v1/plans/'.$plan['id'].'/close', [
            'expectedVersion' => 4,
        ], ['Idempotency-Key' => 'close-incomplete-0001'])->assertUnprocessable()->assertJsonPath('error.code', 'incomplete_explanation_required');

        $closure = $this->postJson('/api/v1/plans/'.$plan['id'].'/close', [
            'expectedVersion' => 4,
            'incompleteExplanation' => 'Private reason: schedule conflict.',
        ], ['Idempotency-Key' => 'close-success-0001'])->assertOk()->assertJsonPath('data.revision', 1)
            ->assertJsonPath('data.metrics.completionRate', 11)
            ->assertJsonPath('data.metrics.coverage.0', 'individual')
            ->json('data');

        $this->postJson('/api/v1/plans/'.$plan['id'].'/close', [
            'expectedVersion' => 4,
            'incompleteExplanation' => 'Private reason: schedule conflict.',
        ], ['Idempotency-Key' => 'close-success-0001'])->assertOk()->assertJsonPath('data.id', $closure['id']);
        $this->postJson('/api/v1/plans/'.$plan['id'].'/close', [
            'expectedVersion' => 3,
            'incompleteExplanation' => 'A different private explanation.',
        ], ['Idempotency-Key' => 'close-success-0001'])->assertConflict()->assertJsonPath('error.code', 'idempotency_key_reused');
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->patchJson('/api/v1/occurrences/'.$occurrence['id'], [
            'expectedVersion' => 1,
            'status' => 'blocked',
        ])->assertConflict()->assertJsonPath('error.code', 'plan_closed');

        $privatePayload = DB::table('plan_closure_private_payloads')->where('closure_id', $closure['id'])->value('encrypted_snapshot');
        $this->assertIsString($privatePayload);
        $this->assertStringNotContainsString('Private reason: schedule conflict.', $privatePayload);
        $this->assertDatabaseHas('outbox_messages', [
            'aggregate_id' => $plan['id'],
            'event_type' => 'plan.closed',
        ]);

        $this->postJson('/api/v1/plans/'.$plan['id'].'/reopen', [
            'expectedVersion' => 5,
        ], ['Idempotency-Key' => 'plan-reopen-0001'])->assertOk()->assertJsonPath('data.workingRevision', 2);
        $this->postJson('/api/v1/plans/'.$plan['id'].'/reopen', [
            'expectedVersion' => 5,
        ], ['Idempotency-Key' => 'plan-reopen-0001'])->assertOk()->assertJsonPath('data.workingRevision', 2);

        $this->postJson('/api/v1/plans/'.$plan['id'].'/close', [
            'expectedVersion' => 6,
            'incompleteExplanation' => 'Correction completed.',
        ], ['Idempotency-Key' => 'close-success-0002'])->assertOk()->assertJsonPath('data.revision', 2);

        $this->getJson('/api/v1/plans/'.$plan['id'].'/closures')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $otherLeader = User::factory()->create();
        $this->actingAs($otherLeader, 'app')
            ->getJson('/api/v1/plans/'.$plan['id'])
            ->assertNotFound();
    }

    public function test_activation_rejects_activities_outside_the_selected_theme(): void
    {
        $organization = Organization::create(['name' => 'Invalid Pick Three Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $activities = $this->createPublishedActivities();
        $draft = $this->actingAs($leader, 'app')
            ->postJson('/api/v1/plan-drafts', [
                'organizationId' => $organization->id,
                'month' => '2026-10',
            ])->assertCreated()->json('data');

        $this->patchJson('/api/v1/plan-drafts/'.$draft['id'], [
            'expectedVersion' => 0,
            'selectedTheme' => 'develop',
            'selectedActivityIds' => array_column($activities, 'id'),
        ])->assertOk();

        $this->postJson('/api/v1/plan-drafts/'.$draft['id'].'/activate', [
            'expectedVersion' => 1,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('plans', 0);
    }

    public function test_activation_enforces_one_plan_per_leader_and_month(): void
    {
        $organization = Organization::create(['name' => 'One Plan Per Month Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $activities = $this->createPublishedActivities();
        $this->actingAs($leader, 'app');

        foreach ([1, 2] as $draftNumber) {
            $draft = $this->postJson('/api/v1/plan-drafts', [
                'organizationId' => $organization->id,
                'month' => '2026-10',
                'title' => 'Plan '.$draftNumber,
            ])->assertCreated()->json('data');
            $this->patchJson('/api/v1/plan-drafts/'.$draft['id'], [
                'expectedVersion' => 0,
                'selectedTheme' => 'care',
                'selectedActivityIds' => array_column($activities, 'id'),
            ])->assertOk();
            $activation = $this->postJson('/api/v1/plan-drafts/'.$draft['id'].'/activate', [
                'expectedVersion' => 1,
            ]);

            if ($draftNumber === 1) {
                $activation->assertCreated();
            } else {
                $activation->assertConflict()->assertJsonPath('error.code', 'plan_month_exists');
            }
        }

        $this->assertDatabaseCount('plans', 1);
    }

    public function test_planning_api_requires_an_application_session(): void
    {
        $this->getJson('/api/v1/plans?month=2026-10&organizationId=01J00000000000000000000000')
            ->assertUnauthorized();
        $this->get('/api/v1/plans?month=2026-10&organizationId=01J00000000000000000000000')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $unverifiedLeader = User::factory()->unverified()->create();
        $this->actingAs($unverifiedLeader, 'app')
            ->getJson('/api/v1/plans?month=2026-10&organizationId=01J00000000000000000000000')
            ->assertForbidden();
    }

    public function test_activity_catalogue_filters_approved_source_and_hides_another_leaders_custom_activity(): void
    {
        $organization = Organization::create(['name' => 'Catalogue API Organization']);
        $leader = User::factory()->create();
        $otherLeader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $this->addMembership($organization, $otherLeader);
        $sourceActivities = $this->createPublishedActivities();

        $customActivityId = (string) Str::ulid();
        $customVersionId = (string) Str::ulid();
        DB::table('activities')->insert([
            'id' => $customActivityId,
            'stable_activity_key' => 'private-custom-develop',
            'kind' => 'custom',
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('activity_versions')->insert([
            'id' => $customVersionId,
            'activity_id' => $customActivityId,
            'version_number' => 1,
            'theme' => 'develop',
            'scope' => 'team',
            'adapted_title' => 'Private synthetic Develop activity',
            'description' => 'Owner-scoped test content.',
            'content_hash' => hash('sha256', 'private-custom'),
            'created_at' => now(),
        ]);
        DB::table('activities')->where('id', $customActivityId)->update(['current_version_id' => $customVersionId]);

        $this->actingAs($leader, 'app')
            ->getJson('/api/v1/activities?organizationId='.$organization->id.'&theme=care&scope=individual')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.currentVersion.scope', 'individual');

        $this->actingAs($leader, 'app')
            ->getJson('/api/v1/activities/'.$customActivityId.'?organizationId='.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.id', $customActivityId);

        $this->actingAs($otherLeader, 'app')
            ->getJson('/api/v1/activities/'.$customActivityId.'?organizationId='.$organization->id)
            ->assertNotFound();

        $this->assertCount(3, $sourceActivities);
    }

    public function test_close_publishes_only_the_allowlisted_report_to_assigned_taleed_users(): void
    {
        Config::set('talent.organization_sharing_enabled', true);
        $organization = Organization::create(['name' => 'Synthetic Report Organization']);
        $leader = User::factory()->create();
        $analyst = User::factory()->create();
        $admin = User::factory()->create();
        $this->addMembership($organization, $leader);
        $activities = $this->createPublishedActivities();
        OrganizationAnalystAssignment::create([
            'organization_id' => $organization->id,
            'user_id' => $analyst->id,
            'assigned_by' => null,
            'assigned_at' => now(),
        ]);

        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Synthetic report plan',
            'focus_theme' => 'care',
            'status' => 'active',
        ]);
        foreach ($activities as $index => $activity) {
            $versionId = DB::table('activities')->where('id', $activity['id'])->value('current_version_id');
            PlanCommitment::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'activity_version_id' => $versionId,
                'pinned_theme' => 'care',
                'pinned_scope' => $activity['scope'],
                'display_order' => $index + 1,
                'status' => 'active',
                'created_at' => now(),
            ]);
        }

        $this->actingAs($leader, 'app')
            ->postJson('/api/v1/plans/'.$plan->id.'/close', [
                'expectedVersion' => 0,
                'incompleteExplanation' => 'Private synthetic explanation.',
            ], ['Idempotency-Key' => 'synthetic-report-close-01'])
            ->assertOk();
        $this->postJson('/api/v1/plans/'.$plan->id.'/reopen', [
            'expectedVersion' => 1,
        ], ['Idempotency-Key' => 'synthetic-report-reopen-01'])
            ->assertOk()
            ->assertJsonPath('data.workingRevision', 2);
        $this->postJson('/api/v1/plans/'.$plan->id.'/close', [
            'expectedVersion' => 2,
        ], ['Idempotency-Key' => 'synthetic-report-close-02'])
            ->assertOk()
            ->assertJsonPath('data.revision', 2);

        $report = $this->actingAs($analyst, 'app')
            ->getJson('/api/v1/portfolio/reports?month=2026-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.organizationId', $organization->id)
            ->assertJsonPath('data.0.closedPlans', 1)
            ->assertJsonPath('data.0.version', 2)
            ->assertJsonPath('data.0.activityCounts.care', 3)
            ->json('data.0');
        self::assertArrayNotHasKey('ownerId', $report);
        self::assertStringNotContainsString('Private synthetic explanation.', json_encode($report));

        $this->actingAs($admin, 'app')
            ->getJson('/api/v1/portfolio/reports?month=2026-10')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        Config::set('talent.organization_sharing_enabled', false);
    }

    public function test_monthly_reports_are_unavailable_when_organization_sharing_is_disabled(): void
    {
        Config::set('talent.organization_sharing_enabled', false);
        $analyst = User::factory()->create();

        $this->actingAs($analyst, 'app')
            ->getJson('/api/v1/portfolio/reports?month=2026-10')
            ->assertNotFound();
    }

    public function test_custom_develop_activities_are_versioned_private_and_bookmarks_follow_visibility(): void
    {
        $organization = Organization::create(['name' => 'Custom Activity Organization']);
        $leader = User::factory()->create();
        $otherLeader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $this->addMembership($organization, $otherLeader);

        $activity = $this->actingAs($leader, 'app')
            ->postJson('/api/v1/custom-activities', [
                'organizationId' => $organization->id,
                'scope' => 'team',
                'title' => 'Run a learning session',
                'description' => 'Invite the team to share one useful practice.',
                'steps' => ['Choose a topic', 'Share one practice'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'custom')
            ->assertJsonPath('data.currentVersion.theme', 'develop')
            ->assertJsonPath('data.currentVersion.version', 1)
            ->json('data');

        $this->patchJson('/api/v1/custom-activities/'.$activity['id'], [
            'expectedVersion' => 1,
            'title' => 'Host a learning session',
        ])->assertOk()->assertJsonPath('data.currentVersion.version', 2);

        $this->patchJson('/api/v1/custom-activities/'.$activity['id'], [
            'expectedVersion' => 1,
            'title' => 'Stale custom edit',
        ])->assertConflict()->assertJsonPath('error.currentVersion', 2);
        $this->assertDatabaseCount('activity_versions', 2);
        $this->assertDatabaseHas('activity_versions', [
            'activity_id' => $activity['id'],
            'version_number' => 1,
            'adapted_title' => 'Run a learning session',
        ]);

        $this->putJson('/api/v1/activities/'.$activity['id'].'/bookmark?organizationId='.$organization->id)
            ->assertOk()
            ->assertJsonPath('data.id', $activity['id']);
        $this->getJson('/api/v1/bookmarks?organizationId='.$organization->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($otherLeader, 'app')
            ->getJson('/api/v1/activities/'.$activity['id'].'?organizationId='.$organization->id)
            ->assertNotFound();
        $this->putJson('/api/v1/activities/'.$activity['id'].'/bookmark?organizationId='.$organization->id)
            ->assertNotFound();
        $this->patchJson('/api/v1/custom-activities/'.$activity['id'], [
            'expectedVersion' => 2,
            'title' => 'Unauthorized edit',
        ])->assertNotFound();

        $this->actingAs($leader, 'app')
            ->postJson('/api/v1/custom-activities/'.$activity['id'].'/retire', ['expectedVersion' => 2])
            ->assertOk()
            ->assertJsonPath('data.status', 'retired');
        $this->getJson('/api/v1/bookmarks?organizationId='.$organization->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('bookmarks', 1);
    }

    public function test_only_owner_custom_develop_activities_can_be_added_to_develop_plans(): void
    {
        $organization = Organization::create(['name' => 'Develop Commitment Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $sourceActivities = $this->createPublishedActivities('develop');
        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Develop plan',
            'focus_theme' => 'develop',
            'status' => 'active',
        ]);
        foreach ($sourceActivities as $index => $sourceActivity) {
            $versionId = DB::table('activities')->where('id', $sourceActivity['id'])->value('current_version_id');
            PlanCommitment::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'activity_version_id' => $versionId,
                'pinned_theme' => 'develop',
                'pinned_scope' => $sourceActivity['scope'],
                'display_order' => $index + 1,
                'status' => 'active',
                'created_at' => now(),
            ]);
        }
        $customActivity = $this->actingAs($leader, 'app')
            ->postJson('/api/v1/custom-activities', [
                'organizationId' => $organization->id,
                'scope' => 'culture',
                'title' => 'Build a shared practice',
                'description' => 'Agree one practice together.',
            ])->assertCreated()->json('data');

        $this->postJson('/api/v1/plans/'.$plan->id.'/commitments', [
            'expectedVersion' => 0,
            'activityId' => $customActivity['id'],
        ], ['Idempotency-Key' => 'develop-commitment-0001'])
            ->assertCreated()
            ->assertJsonPath('data.theme', 'develop');
        $this->postJson('/api/v1/plans/'.$plan->id.'/commitments', [
            'expectedVersion' => 0,
            'activityId' => $customActivity['id'],
        ], ['Idempotency-Key' => 'develop-commitment-0001'])
            ->assertOk();
        $this->assertDatabaseCount('plan_commitments', 4);
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'lock_version' => 1]);
    }

    public function test_one_off_schedule_is_restricted_to_develop(): void
    {
        $expander = app(ScheduleExpander::class);

        $this->assertSame(
            ['2026-10-15'],
            $expander->expand([
                'cadence' => 'one_off',
                'startDate' => '2026-10-15',
                'endDate' => '2026-10-15',
            ], '2026-10', 'develop'),
        );
        $this->expectException(\InvalidArgumentException::class);
        $expander->expand([
            'cadence' => 'one_off',
            'startDate' => '2026-10-15',
            'endDate' => '2026-10-15',
        ], '2026-10', 'care');
    }

    public function test_occurrence_notes_are_encrypted_owner_private_and_versioned(): void
    {
        $organization = Organization::create(['name' => 'Private Occurrence Note Organization']);
        $leader = User::factory()->create();
        $otherLeader = User::factory()->create();
        $this->addMembership($organization, $leader);
        $this->addMembership($organization, $otherLeader);
        $activities = $this->createPublishedActivities();
        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Private notes plan',
            'focus_theme' => 'care',
            'status' => 'active',
        ]);
        $versionId = DB::table('activities')->where('id', $activities[0]['id'])->value('current_version_id');
        $commitment = PlanCommitment::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'activity_version_id' => $versionId,
            'pinned_theme' => 'care',
            'pinned_scope' => $activities[0]['scope'],
            'display_order' => 1,
            'status' => 'active',
            'created_at' => now(),
        ]);
        $scheduleVersionId = (string) Str::ulid();
        DB::table('commitment_schedule_versions')->insert([
            'id' => $scheduleVersionId,
            'organization_id' => $organization->id,
            'commitment_id' => $commitment->id,
            'version_number' => 1,
            'cadence' => 'daily',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
            'effective_from' => '2026-10-01',
            'created_at' => now(),
        ]);
        $occurrence = Occurrence::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'owner_user_id' => $leader->id,
            'commitment_id' => $commitment->id,
            'generation_date' => '2026-10-01',
            'scheduled_date' => '2026-10-01',
            'schedule_version_id' => $scheduleVersionId,
            'status' => 'scheduled',
            'lock_version' => 0,
        ]);
        $note = 'Private synthetic occurrence note.';

        $this->actingAs($leader, 'app')
            ->putJson('/api/v1/occurrences/'.$occurrence->id.'/note', [
                'expectedVersion' => 0,
                'note' => $note,
            ])
            ->assertOk()
            ->assertJsonPath('data.note', $note)
            ->assertJsonPath('data.lockVersion', 1);

        $ciphertext = DB::table('occurrence_private_payloads')->where('occurrence_id', $occurrence->id)->value('encrypted_payload');
        $this->assertIsString($ciphertext);
        $this->assertStringNotContainsString($note, $ciphertext);
        $this->assertDatabaseHas('occurrence_events', [
            'occurrence_id' => $occurrence->id,
            'event_type' => 'private_note_updated',
        ]);
        $this->assertStringNotContainsString($note, DB::table('occurrence_events')->where('occurrence_id', $occurrence->id)->value('event_type'));

        $this->getJson('/api/v1/calendar?organizationId='.$organization->id.'&month=2026-10')
            ->assertOk()
            ->assertJsonPath('data.0.note', $note);
        $this->putJson('/api/v1/occurrences/'.$occurrence->id.'/note', [
            'expectedVersion' => 0,
            'note' => 'Stale note.',
        ])->assertConflict();

        $this->actingAs($otherLeader, 'app')
            ->getJson('/api/v1/calendar?organizationId='.$organization->id.'&month=2026-10')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->putJson('/api/v1/occurrences/'.$occurrence->id.'/note', [
            'expectedVersion' => 1,
            'note' => 'Unauthorized note.',
        ])->assertNotFound();

        $this->actingAs($leader, 'app')
            ->putJson('/api/v1/occurrences/'.$occurrence->id.'/note', [
                'expectedVersion' => 1,
                'note' => '',
            ])
            ->assertOk()
            ->assertJsonPath('data.note', '');
        $this->assertDatabaseMissing('occurrence_private_payloads', ['occurrence_id' => $occurrence->id]);
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

    /** @return array<int, array{id: string, scope: string}> */
    private function createPublishedActivities(string $theme = 'care'): array
    {
        $activities = [];
        foreach (['individual', 'culture', 'team'] as $index => $scope) {
            $activityId = (string) Str::ulid();
            $versionId = (string) Str::ulid();
            DB::table('activities')->insert([
                'id' => $activityId,
                'stable_activity_key' => 'phase3-'.$theme.'-'.$scope,
                'kind' => 'source',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('activity_versions')->insert([
                'id' => $versionId,
                'activity_id' => $activityId,
                'version_number' => 1,
                'theme' => $theme,
                'scope' => $scope,
                'adapted_title' => 'Approved synthetic '.$scope,
                'description' => 'Synthetic feature fixture.',
                'content_hash' => hash('sha256', 'phase3-'.$scope),
                'approved_at' => now(),
                'created_at' => now(),
            ]);
            DB::table('activities')->where('id', $activityId)->update(['current_version_id' => $versionId]);
            $activities[] = ['id' => $activityId, 'scope' => $scope];
        }

        return $activities;
    }
}
