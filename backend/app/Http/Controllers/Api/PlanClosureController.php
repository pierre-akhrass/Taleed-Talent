<?php

namespace App\Http\Controllers\Api;

use App\Domain\Talent\MonthlySummaryPublisher;
use App\Domain\Talent\PlanMetricsCalculator;
use App\Http\Controllers\Controller;
use App\Models\Occurrence;
use App\Models\Plan;
use App\Models\PlanClosure;
use App\Models\PlanClosurePrivatePayload;
use App\Models\User;
use App\Services\Talent\IdempotencyKeyService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

class PlanClosureController extends Controller
{
    public function close(
        Request $request,
        string $planId,
        IdempotencyKeyService $idempotency,
        MonthlySummaryPublisher $summaryPublisher,
        PlanMetricsCalculator $metricsCalculator,
    ): JsonResponse {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'incompleteExplanation' => ['nullable', 'string', 'max:4000'],
        ]);
        $user = $request->user('app');
        $idempotencyKey = $request->header('Idempotency-Key');
        abort_unless(is_string($idempotencyKey) && strlen($idempotencyKey) >= 16 && strlen($idempotencyKey) <= 128, 422);
        $planContext = $this->ownedPlanQuery($user, $planId)->firstOrFail();
        $idempotencyPayload = ['planId' => $planId, 'request' => $data];

        try {
            $execution = $idempotency->run(
                $user,
                $planContext->organization_id,
                'plan.close',
                $idempotencyKey,
                $idempotencyPayload,
                function () use ($data, $planId, $user, $summaryPublisher, $metricsCalculator): array {
                    return DB::transaction(function () use ($data, $planId, $user, $summaryPublisher, $metricsCalculator): array {
                        $plan = $this->ownedPlanQuery($user, $planId)->lockForUpdate()->firstOrFail();
                        if ($plan->status === 'closed') {
                            return ['closure' => $plan->closures()->where('revision_number', $plan->working_revision)->firstOrFail()];
                        }
                        if ($plan->lock_version !== $data['expectedVersion']) {
                            return ['conflict' => $plan->lock_version];
                        }

                        $occurrences = Occurrence::query()->where('plan_id', $plan->id)->get();
                        $explanation = trim((string) ($data['incompleteExplanation'] ?? ''));
                        $commitments = $plan->commitments()->get(['id', 'activity_version_id', 'pinned_theme', 'pinned_scope']);
                        $metrics = $metricsCalculator->calculate(
                            $occurrences->map(fn (Occurrence $occurrence): array => [
                                'commitmentId' => $occurrence->commitment_id,
                                'status' => $occurrence->status,
                            ])->all(),
                            $commitments->map(fn ($commitment): array => [
                                'id' => $commitment->id,
                                'scope' => $commitment->pinned_scope,
                            ])->all(),
                        );
                        if ($metrics['completed'] < $metrics['eligible'] && $explanation === '') {
                            return ['incomplete' => true];
                        }

                        $activityCounts = array_fill_keys(['care', 'develop', 'enable', 'recognition'], 0);
                        $scopeCounts = array_fill_keys(['individual', 'culture', 'team'], 0);
                        foreach ($commitments as $commitment) {
                            $activityCounts[$commitment->pinned_theme]++;
                            $scopeCounts[$commitment->pinned_scope]++;
                        }
                        $safeFacts = [
                            'focusTheme' => $plan->focus_theme,
                            'activityCounts' => $activityCounts,
                            'scopeCounts' => $scopeCounts,
                            'scheduled' => $metrics['scheduled'],
                            'completed' => $metrics['completed'],
                            'blocked' => $metrics['blocked'],
                            'inProgress' => $metrics['inProgress'],
                            'cancelled' => $metrics['cancelled'],
                            'eligible' => $metrics['eligible'],
                            'completionRate' => $metrics['rate'],
                            'coverage' => $metrics['coverage'],
                        ];
                        $pinnedVersionIds = $commitments->pluck('activity_version_id')->values()->all();
                        $closureId = (string) Str::ulid();
                        $closedAt = now();
                        $closure = PlanClosure::create([
                            'id' => $closureId,
                            'organization_id' => $plan->organization_id,
                            'owner_user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'revision_number' => $plan->working_revision,
                            'closed_at' => $closedAt,
                            'safe_facts_json' => $safeFacts,
                            'pinned_version_ids_json' => $pinnedVersionIds,
                            'metrics_definition_version' => 'metrics-v1',
                            'public_integrity_hash' => hash('sha256', json_encode([
                                'planId' => $plan->id,
                                'revision' => $plan->working_revision,
                                'safeFacts' => $safeFacts,
                                'pinnedVersionIds' => $pinnedVersionIds,
                            ], JSON_THROW_ON_ERROR)),
                        ]);

                        if ($explanation !== '') {
                            $privateSnapshot = Crypt::encryptString(json_encode([
                                'incompleteExplanation' => $explanation,
                            ], JSON_THROW_ON_ERROR));
                            PlanClosurePrivatePayload::create([
                                'closure_id' => $closure->id,
                                'organization_id' => $plan->organization_id,
                                'owner_user_id' => $user->id,
                                'encrypted_snapshot' => $privateSnapshot,
                                'key_version' => (string) config('app.key_version'),
                                'created_at' => $closedAt,
                            ]);
                        }

                        $plan->status = 'closed';
                        $plan->closed_at = $closedAt;
                        $plan->lock_version++;
                        $plan->save();

                        $summaryPublisher->publishLatestFor($plan);

                        DB::table('outbox_messages')->insert([
                            'aggregate_type' => 'plan',
                            'aggregate_id' => $plan->id,
                            'event_type' => 'plan.closed',
                            'deduplication_key' => 'plan.closed:'.$plan->id.':'.$closure->revision_number,
                            'available_at' => $closedAt,
                            'attempts' => 0,
                            'created_at' => $closedAt,
                            'updated_at' => $closedAt,
                        ]);

                        return ['closure' => $closure, 'plan' => $plan];
                    });
                },
                fn (array $result): ?string => $result['closure']->id ?? null,
            );
        } catch (JsonException $exception) {
            report($exception);

            return response()->json(['error' => ['code' => 'closure_failed', 'message' => 'The plan could not be closed.']], 500);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'plan_closures_plan_id_revision_number_unique')) {
                return response()->json(['error' => ['code' => 'closure_conflict', 'message' => 'This revision has already been closed.']], 409);
            }
            throw $exception;
        }

        if (in_array($execution['state'], ['key_reused', 'in_progress'], true)) {
            return response()->json(['error' => [
                'code' => $execution['state'] === 'key_reused' ? 'idempotency_key_reused' : 'idempotency_in_progress',
                'message' => 'The idempotency key cannot be used for this request.',
            ]], 409);
        }
        $result = $execution['state'] === 'replay'
            ? ['closure' => PlanClosure::query()
                ->whereKey($execution['reference'])
                ->where('plan_id', $planId)
                ->where('owner_user_id', $user->id)
                ->firstOrFail()]
            : $execution['result'];

        if (array_key_exists('conflict', $result)) {
            return $this->versionConflict($request, $result['conflict']);
        }
        if (array_key_exists('incomplete', $result)) {
            return response()->json([
                'error' => [
                    'code' => 'incomplete_explanation_required',
                    'message' => 'Add a private explanation before closing an incomplete plan.',
                    'fields' => ['incompleteExplanation' => ['This field is required for an incomplete plan.']],
                ],
            ], 422);
        }

        return response()->json(['data' => $this->closureData($result['closure'])]);
    }

    public function reopen(Request $request, string $planId, IdempotencyKeyService $idempotency): JsonResponse
    {
        $data = $request->validate(['expectedVersion' => ['required', 'integer', 'min:0']]);
        $user = $request->user('app');
        $idempotencyKey = $request->header('Idempotency-Key');
        abort_unless(is_string($idempotencyKey) && strlen($idempotencyKey) >= 16 && strlen($idempotencyKey) <= 128, 422);
        $planContext = $this->ownedPlanQuery($user, $planId)->firstOrFail();
        $idempotencyPayload = ['planId' => $planId, 'request' => $data];
        $execution = $idempotency->run(
            $user,
            $planContext->organization_id,
            'plan.reopen',
            $idempotencyKey,
            $idempotencyPayload,
            fn (): array => DB::transaction(function () use ($data, $planId, $user): array {
                $plan = $this->ownedPlanQuery($user, $planId)->lockForUpdate()->firstOrFail();
                if ($plan->lock_version !== $data['expectedVersion']) {
                    return ['conflict' => $plan->lock_version];
                }
                if ($plan->status !== 'closed') {
                    return ['invalid' => true];
                }

                $plan->status = 'active';
                $plan->closed_at = null;
                $plan->working_revision++;
                $plan->lock_version++;
                $plan->save();

                return ['plan' => $plan];
            }),
            fn (array $result): ?string => $result['plan']->id ?? null,
        );

        if (in_array($execution['state'], ['key_reused', 'in_progress'], true)) {
            return response()->json(['error' => [
                'code' => $execution['state'] === 'key_reused' ? 'idempotency_key_reused' : 'idempotency_in_progress',
                'message' => 'The idempotency key cannot be used for this request.',
            ]], 409);
        }
        $result = $execution['state'] === 'replay'
            ? ['plan' => $this->ownedPlanQuery($user, $planId)->firstOrFail()]
            : $execution['result'];

        if (array_key_exists('conflict', $result)) {
            return $this->versionConflict($request, $result['conflict']);
        }
        if (array_key_exists('invalid', $result)) {
            return response()->json(['error' => ['code' => 'plan_not_closed', 'message' => 'Only a closed plan can be reopened.']], 409);
        }

        return response()->json(['data' => [
            'id' => $result['plan']->id,
            'status' => $result['plan']->status,
            'workingRevision' => $result['plan']->working_revision,
            'lockVersion' => $result['plan']->lock_version,
        ]]);
    }

    public function index(Request $request, string $planId): JsonResponse
    {
        $plan = $this->ownedPlanQuery($request->user('app'), $planId)->firstOrFail();
        $closures = $plan->closures()->orderBy('revision_number')->get();

        return response()->json(['data' => $closures->map(fn (PlanClosure $closure): array => $this->closureData($closure))->values()]);
    }

    private function ownedPlanQuery(User $user, string $planId)
    {
        return Plan::query()
            ->whereKey($planId)
            ->where('owner_user_id', $user->id)
            ->whereExists(fn ($membership) => $membership
                ->selectRaw('1')
                ->from('organization_memberships')
                ->whereColumn('organization_memberships.organization_id', 'plans.organization_id')
                ->where('organization_memberships.user_id', $user->id)
                ->where('organization_memberships.status', 'active')
                ->whereNull('organization_memberships.revoked_at'));
    }

    private function closureData(PlanClosure $closure): array
    {
        return [
            'id' => $closure->id,
            'planId' => $closure->plan_id,
            'revision' => $closure->revision_number,
            'closedAt' => $closure->closed_at->toIso8601String(),
            'metrics' => $closure->safe_facts_json,
        ];
    }

    private function versionConflict(Request $request, int $currentVersion): JsonResponse
    {
        return response()->json(['error' => [
            'code' => 'version_conflict',
            'message' => 'This plan changed since it was loaded.',
            'requestId' => $request->attributes->get('request_id', ''),
            'currentVersion' => $currentVersion,
        ]], 409);
    }
}
