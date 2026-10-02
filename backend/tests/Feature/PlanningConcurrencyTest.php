<?php

namespace Tests\Feature;

use App\Models\Occurrence;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanCommitment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PlanningConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_simultaneous_reschedules_cannot_claim_the_same_commitment_date_on_mysql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test verifies MySQL row-lock and unique-slot behavior.');
        }

        $organization = Organization::create(['name' => 'Concurrent Schedule Organization']);
        $leader = User::factory()->create();
        DB::table('organization_memberships')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $organization->id,
            'user_id' => $leader->id,
            'role' => 'leader',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Concurrent schedule plan',
            'focus_theme' => 'care',
            'status' => 'active',
            'working_revision' => 1,
            'lock_version' => 0,
        ]);
        $activityId = (string) Str::ulid();
        $versionId = (string) Str::ulid();
        DB::table('activities')->insert([
            'id' => $activityId,
            'stable_activity_key' => 'concurrent-schedule-activity',
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
            'scope' => 'individual',
            'adapted_title' => 'Concurrent synthetic activity',
            'description' => 'Synthetic fixture.',
            'content_hash' => hash('sha256', 'concurrent-schedule'),
            'approved_at' => now(),
            'created_at' => now(),
        ]);
        DB::table('activities')->where('id', $activityId)->update(['current_version_id' => $versionId]);
        $commitment = PlanCommitment::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'activity_version_id' => $versionId,
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
            'commitment_id' => $commitment->id,
            'version_number' => 1,
            'cadence' => 'weekly',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'weekdays_json' => json_encode([1]),
            'effective_from' => '2026-10-01',
            'created_at' => now(),
        ]);
        $occurrences = [];
        foreach (['2026-10-05', '2026-10-06'] as $date) {
            $occurrences[] = Occurrence::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'owner_user_id' => $leader->id,
                'commitment_id' => $commitment->id,
                'generation_date' => $date,
                'scheduled_date' => $date,
                'schedule_version_id' => $scheduleVersionId,
                'status' => 'scheduled',
                'lock_version' => 0,
            ]);
        }

        DB::commit();
        $barrierPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'talent-reschedule-'.Str::uuid().'.ready';
        $processes = [];
        $childCode = <<<'PHP'
require 'vendor/autoload.php';
$application = require 'bootstrap/app.php';
$application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$request = Illuminate\Http\Request::create('/api/v1/occurrences/'.$argv[2].'/reschedule', 'POST', ['expectedVersion' => 0, 'scheduledDate' => '2026-10-07']);
$request->headers->set('Idempotency-Key', $argv[4]);
$request->setUserResolver(fn () => $user);
while (! is_file($argv[3])) {
    usleep(1000);
}
$response = app(App\Http\Controllers\Api\CommitmentScheduleController::class)->reschedule(
    $request,
    $argv[2],
    app(App\Services\Talent\IdempotencyKeyService::class),
);
echo json_encode(['status' => $response->getStatusCode(), 'body' => $response->getData(true)], JSON_THROW_ON_ERROR);
PHP;

        try {
            foreach ($occurrences as $occurrence) {
                $process = new Process([
                    PHP_BINARY,
                    '-r',
                    $childCode,
                    (string) $leader->id,
                    $occurrence->id,
                    $barrierPath,
                    'concurrent-reschedule-'.$occurrence->id,
                ], base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }

            file_put_contents($barrierPath, 'start');
            $statuses = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
                $statuses[] = $result['status'];
                if ($result['status'] === 409) {
                    $this->assertSame('occurrence_date_collision', $result['body']['error']['code']);
                }
            }

            sort($statuses);
            $this->assertSame([200, 409], $statuses);
            $this->assertSame(1, Occurrence::query()->where('active_scheduled_date', '2026-10-07')->count());
            $this->assertSame(['2026-10-05', '2026-10-06'], Occurrence::query()
                ->where('commitment_id', $commitment->id)
                ->orderBy('generation_date')
                ->pluck('generation_date')
                ->map(fn ($date): string => $date->format('Y-m-d'))
                ->all());
            $this->assertDatabaseCount('occurrence_events', 1);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }
            if (is_file($barrierPath)) {
                unlink($barrierPath);
            }
            DB::table('idempotency_requests')->where('user_id', $leader->id)->delete();
            DB::table('occurrence_events')->whereIn('occurrence_id', collect($occurrences)->pluck('id'))->delete();
            DB::table('occurrences')->where('commitment_id', $commitment->id)->delete();
            DB::table('commitment_schedule_versions')->where('commitment_id', $commitment->id)->delete();
            DB::table('plan_commitments')->where('id', $commitment->id)->delete();
            DB::table('activities')->where('id', $activityId)->update(['current_version_id' => null]);
            DB::table('activity_versions')->where('activity_id', $activityId)->delete();
            DB::table('activities')->where('id', $activityId)->delete();
            DB::table('plans')->where('id', $plan->id)->delete();
            DB::table('organization_memberships')->where('organization_id', $organization->id)->delete();
            DB::table('organizations')->where('id', $organization->id)->delete();
            DB::table('users')->where('id', $leader->id)->delete();
        }
    }

    public function test_simultaneous_close_requests_create_one_closure_and_one_outbox_event_on_mysql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test verifies MySQL row-lock behavior.');
        }

        $organization = Organization::create(['name' => 'Concurrent Close Organization']);
        $leader = User::factory()->create();
        DB::table('organization_memberships')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $organization->id,
            'user_id' => $leader->id,
            'role' => 'leader',
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $plan = Plan::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $leader->id,
            'month' => '2026-10-01',
            'timezone' => 'Asia/Riyadh',
            'title' => 'Concurrent close plan',
            'focus_theme' => 'care',
            'status' => 'active',
            'working_revision' => 1,
            'lock_version' => 0,
        ]);

        DB::commit();
        $barrierPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'talent-close-'.Str::uuid().'.ready';
        $processes = [];
        $childCode = <<<'PHP'
require 'vendor/autoload.php';
$application = require 'bootstrap/app.php';
$application->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$request = Illuminate\Http\Request::create('/api/v1/plans/'.$argv[2].'/close', 'POST', ['expectedVersion' => 0]);
$request->headers->set('Idempotency-Key', $argv[4]);
$request->setUserResolver(fn () => $user);
while (! is_file($argv[3])) {
    usleep(1000);
}
$response = app(App\Http\Controllers\Api\PlanClosureController::class)->close(
    $request,
    $argv[2],
    app(App\Services\Talent\IdempotencyKeyService::class),
    app(App\Domain\Talent\MonthlySummaryPublisher::class),
    app(App\Domain\Talent\PlanMetricsCalculator::class),
);
echo json_encode(['status' => $response->getStatusCode(), 'body' => $response->getData(true)], JSON_THROW_ON_ERROR);
PHP;

        try {
            foreach ([1, 2] as $_requestNumber) {
                $process = new Process([
                    PHP_BINARY,
                    '-r',
                    $childCode,
                    (string) $leader->id,
                    $plan->id,
                    $barrierPath,
                    'concurrent-plan-close-key-01',
                ], base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
            }

            file_put_contents($barrierPath, 'start');
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame(200, $result['status']);
            }

            $this->assertDatabaseCount('plan_closures', 1);
            $this->assertDatabaseCount('outbox_messages', 1);
            $this->assertDatabaseCount('idempotency_requests', 1);
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }
            if (is_file($barrierPath)) {
                unlink($barrierPath);
            }
            DB::table('idempotency_requests')->where('user_id', $leader->id)->delete();
            DB::table('outbox_messages')->where('aggregate_id', $plan->id)->delete();
            DB::table('plan_closures')->where('plan_id', $plan->id)->delete();
            DB::table('plans')->where('id', $plan->id)->delete();
            DB::table('organization_memberships')->where('organization_id', $organization->id)->delete();
            DB::table('organizations')->where('id', $organization->id)->delete();
            DB::table('users')->where('id', $leader->id)->delete();
        }
    }
}
