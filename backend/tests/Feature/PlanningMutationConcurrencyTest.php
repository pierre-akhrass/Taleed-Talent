<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanCommitment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PlanningMutationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_simultaneous_plan_activation_creates_one_plan_and_three_pinned_commitments_on_mysql(): void
    {
        $this->requireMySql();

        $organization = Organization::create(['name' => 'Concurrent Activation Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization->id, $leader->id);
        $activityIds = $this->createPublishedActivities('activation');
        $draftId = (string) Str::ulid();
        DB::table('plan_drafts')->insert([
            'id' => $draftId,
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'selected_theme' => 'care',
            'draft_payload_json' => json_encode(['title' => 'Concurrent plan', 'selectedActivityIds' => $activityIds]),
            'step' => 'review',
            'lock_version' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::commit();

        $code = <<<'PHP'
require 'vendor/autoload.php';
$application = require 'bootstrap/app.php';
$application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$request = Illuminate\Http\Request::create('/api/v1/plan-drafts/'.$argv[2].'/activate', 'POST', ['expectedVersion' => 0]);
$request->setUserResolver(fn () => $user);
while (! is_file($argv[3])) {
    usleep(1000);
}
$response = app(App\Http\Controllers\Api\PlanDraftController::class)->activate($request, $argv[2], app(App\Domain\Talent\PickThreeValidator::class));
echo json_encode(['status' => $response->getStatusCode(), 'body' => $response->getData(true)], JSON_THROW_ON_ERROR);
PHP;

        try {
            $results = $this->race($code, [
                [(string) $leader->id, $draftId],
                [(string) $leader->id, $draftId],
            ]);

            $this->assertSame([201, 201], array_column($results, 'status'));
            $this->assertSame($results[0]['body']['data']['id'], $results[1]['body']['data']['id']);
            $this->assertDatabaseCount('plans', 1);
            $this->assertDatabaseCount('plan_commitments', 3);
            $this->assertDatabaseHas('plan_drafts', ['id' => $draftId, 'step' => 'activated', 'lock_version' => 1]);
        } finally {
            $planId = DB::table('plans')->where('organization_id', $organization->id)->value('id');
            if ($planId) {
                DB::table('plan_commitments')->where('plan_id', $planId)->delete();
                DB::table('plans')->where('id', $planId)->delete();
            }
            DB::table('plan_drafts')->where('id', $draftId)->delete();
            $this->deleteActivities($activityIds);
            DB::table('organization_memberships')->where('organization_id', $organization->id)->delete();
            DB::table('organizations')->where('id', $organization->id)->delete();
            DB::table('users')->where('id', $leader->id)->delete();
        }
    }

    public function test_competing_schedule_versions_allow_only_one_expected_plan_version_on_mysql(): void
    {
        $this->requireMySql();

        $organization = Organization::create(['name' => 'Concurrent Schedule Version Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization->id, $leader->id);
        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Schedule version plan',
            'focus_theme' => 'care',
            'status' => 'active',
            'working_revision' => 1,
            'lock_version' => 0,
        ]);
        $activityIds = $this->createPublishedActivities('schedule-version');
        $activityVersionId = DB::table('activities')->where('id', $activityIds[0])->value('current_version_id');
        $commitment = PlanCommitment::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'activity_version_id' => $activityVersionId,
            'pinned_theme' => 'care',
            'pinned_scope' => 'individual',
            'display_order' => 1,
            'status' => 'active',
            'created_at' => now(),
        ]);
        DB::commit();

        $code = <<<'PHP'
require 'vendor/autoload.php';
$application = require 'bootstrap/app.php';
$application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$payload = [
    'expectedVersion' => 0,
    'cadence' => $argv[3],
    'startDate' => '2026-10-01',
    'endDate' => '2026-10-31',
];
if ($argv[3] === 'weekly') {
    $payload['weekdays'] = [1, 3];
}
$request = Illuminate\Http\Request::create('/api/v1/commitments/'.$argv[2].'/schedule-versions', 'POST', $payload);
$request->headers->set('Idempotency-Key', $argv[4]);
$request->setUserResolver(fn () => $user);
while (! is_file($argv[5])) {
    usleep(1000);
}
$response = app(App\Http\Controllers\Api\CommitmentScheduleController::class)->store(
    $request,
    $argv[2],
    app(App\Domain\Talent\ScheduleExpander::class),
    app(App\Services\Talent\IdempotencyKeyService::class),
);
echo json_encode(['status' => $response->getStatusCode(), 'body' => $response->getData(true)], JSON_THROW_ON_ERROR);
PHP;

        try {
            $results = $this->race($code, [
                [(string) $leader->id, $commitment->id, 'daily', 'concurrent-schedule-version-01'],
                [(string) $leader->id, $commitment->id, 'weekly', 'concurrent-schedule-version-02'],
            ]);
            $statuses = array_column($results, 'status');
            sort($statuses);

            $this->assertSame([201, 409], $statuses);
            $this->assertSame(1, DB::table('commitment_schedule_versions')->where('commitment_id', $commitment->id)->count());
            $this->assertSame(1, (int) DB::table('plans')->where('id', $plan->id)->value('lock_version'));
            $winningCadence = DB::table('commitment_schedule_versions')->where('commitment_id', $commitment->id)->value('cadence');
            $expectedOccurrenceCount = $winningCadence === 'daily' ? 31 : 8;
            $this->assertSame($expectedOccurrenceCount, DB::table('occurrences')->where('commitment_id', $commitment->id)->where('status', '!=', 'superseded')->count());
        } finally {
            DB::table('idempotency_requests')->where('user_id', $leader->id)->delete();
            DB::table('occurrence_events')->where('organization_id', $organization->id)->delete();
            DB::table('occurrences')->where('commitment_id', $commitment->id)->delete();
            DB::table('commitment_schedule_versions')->where('commitment_id', $commitment->id)->delete();
            DB::table('plan_commitments')->where('id', $commitment->id)->delete();
            DB::table('plans')->where('id', $plan->id)->delete();
            $this->deleteActivities($activityIds);
            DB::table('organization_memberships')->where('organization_id', $organization->id)->delete();
            DB::table('organizations')->where('id', $organization->id)->delete();
            DB::table('users')->where('id', $leader->id)->delete();
        }
    }

    public function test_simultaneous_reopen_requests_advance_one_working_revision_on_mysql(): void
    {
        $this->requireMySql();

        $organization = Organization::create(['name' => 'Concurrent Reopen Organization']);
        $leader = User::factory()->create();
        $this->addMembership($organization->id, $leader->id);
        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Closed plan',
            'focus_theme' => 'care',
            'status' => 'closed',
            'working_revision' => 1,
            'lock_version' => 1,
            'closed_at' => now(),
        ]);
        $closureId = (string) Str::ulid();
        DB::table('plan_closures')->insert([
            'id' => $closureId,
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'plan_id' => $plan->id,
            'revision_number' => 1,
            'closed_at' => now(),
            'public_integrity_hash' => hash('sha256', 'closed-plan'),
            'safe_facts_json' => json_encode(['scheduled' => 0, 'completed' => 0]),
            'pinned_version_ids_json' => '[]',
            'metrics_definition_version' => 'metrics-v1',
        ]);
        DB::commit();

        $code = <<<'PHP'
require 'vendor/autoload.php';
$application = require 'bootstrap/app.php';
$application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$request = Illuminate\Http\Request::create('/api/v1/plans/'.$argv[2].'/reopen', 'POST', ['expectedVersion' => 1]);
$request->headers->set('Idempotency-Key', $argv[3]);
$request->setUserResolver(fn () => $user);
while (! is_file($argv[4])) {
    usleep(1000);
}
$response = app(App\Http\Controllers\Api\PlanClosureController::class)->reopen(
    $request,
    $argv[2],
    app(App\Services\Talent\IdempotencyKeyService::class),
);
echo json_encode(['status' => $response->getStatusCode(), 'body' => $response->getData(true)], JSON_THROW_ON_ERROR);
PHP;

        try {
            $results = $this->race($code, [
                [(string) $leader->id, $plan->id, 'concurrent-plan-reopen-01'],
                [(string) $leader->id, $plan->id, 'concurrent-plan-reopen-01'],
            ]);
            $statuses = array_column($results, 'status');
            sort($statuses);

            $this->assertSame([200, 200], $statuses);
            $this->assertSame($results[0]['body']['data']['lockVersion'], $results[1]['body']['data']['lockVersion']);
            $this->assertDatabaseHas('plans', [
                'id' => $plan->id,
                'status' => 'active',
                'working_revision' => 2,
                'lock_version' => 2,
            ]);
            $this->assertDatabaseCount('plan_closures', 1);
            $this->assertDatabaseHas('plan_closures', ['id' => $closureId, 'revision_number' => 1]);
        } finally {
            DB::table('idempotency_requests')->where('user_id', $leader->id)->delete();
            DB::table('plan_closures')->where('id', $closureId)->delete();
            DB::table('plans')->where('id', $plan->id)->delete();
            DB::table('organization_memberships')->where('organization_id', $organization->id)->delete();
            DB::table('organizations')->where('id', $organization->id)->delete();
            DB::table('users')->where('id', $leader->id)->delete();
        }
    }

    private function requireMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test verifies MySQL row-lock behavior.');
        }
    }

    private function addMembership(string $organizationId, int $userId): void
    {
        DB::table('organization_memberships')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'role' => 'leader',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int, string> */
    private function createPublishedActivities(string $key): array
    {
        $ids = [];
        foreach (['individual', 'culture', 'team'] as $index => $scope) {
            $activityId = (string) Str::ulid();
            $versionId = (string) Str::ulid();
            DB::table('activities')->insert([
                'id' => $activityId,
                'stable_activity_key' => 'concurrent-'.$key.'-'.$scope,
                'kind' => 'source',
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('activity_versions')->insert([
                'id' => $versionId,
                'activity_id' => $activityId,
                'version_number' => 1,
                'theme' => 'care',
                'scope' => $scope,
                'adapted_title' => 'Concurrent '.$scope,
                'description' => 'Synthetic concurrency fixture.',
                'content_hash' => hash('sha256', $key.'-'.$index),
                'approved_at' => now(),
                'created_at' => now(),
            ]);
            DB::table('activities')->where('id', $activityId)->update(['current_version_id' => $versionId]);
            $ids[] = $activityId;
        }

        return $ids;
    }

    /** @param array<int, string> $activityIds */
    private function deleteActivities(array $activityIds): void
    {
        $activities = DB::table('activities')->whereIn('id', $activityIds)->get(['id', 'current_version_id']);
        foreach ($activities as $activity) {
            DB::table('activities')->where('id', $activity->id)->update(['current_version_id' => null]);
            DB::table('activity_versions')->where('activity_id', $activity->id)->delete();
            DB::table('activities')->where('id', $activity->id)->delete();
        }
    }

    /** @param array<int, array<int, string>> $arguments @return array<int, array{status: int, body: array}> */
    private function race(string $code, array $arguments): array
    {
        $barrierPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'talent-mutation-'.Str::uuid().'.ready';
        $processes = [];

        try {
            foreach ($arguments as $argumentSet) {
                $process = new Process([
                    PHP_BINARY,
                    '-r',
                    $code,
                    ...$argumentSet,
                    $barrierPath,
                ], base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }

            file_put_contents($barrierPath, 'start');
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), 'Exit '.$process->getExitCode().': '.$process->getErrorOutput().$process->getOutput());
                $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }
            if (is_file($barrierPath)) {
                unlink($barrierPath);
            }
        }
    }
}
