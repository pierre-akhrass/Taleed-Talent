<?php

namespace App\Http\Controllers\Api;

use App\Domain\Talent\PlanMetricsCalculator;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanCommitment;
use App\Models\User;
use App\Services\Talent\IdempotencyKeyService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationId' => ['required', 'ulid'],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'cursor' => ['sometimes', 'string', 'max:512'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $this->authorizedOrganization($request->user('app'), $data['organizationId']);
        $limit = $data['limit'] ?? 50;
        $plans = Plan::query()
            ->where('organization_id', $data['organizationId'])
            ->where('owner_user_id', $request->user('app')->id)
            ->whereDate('month', $data['month'].'-01')
            ->when(isset($data['cursor']), fn ($query) => $query->where('id', '>', $data['cursor']))
            ->with(['commitments.activityVersion', 'occurrences'])
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();
        $hasMore = $plans->count() > $limit;
        $plans = $plans->take($limit)->values();

        return response()->json([
            'data' => $plans->map(fn (Plan $plan): array => $this->planData($plan))->values(),
            'meta' => ['nextCursor' => $hasMore ? $plans->last()->id : null],
        ]);
    }

    public function show(Request $request, string $planId): JsonResponse
    {
        $plan = Plan::query()
            ->whereKey($planId)
            ->where('owner_user_id', $request->user('app')->id)
            ->with(['commitments.activityVersion', 'occurrences'])
            ->firstOrFail();
        abort_unless(Gate::forUser($request->user('app'))->allows('view', $plan), 404);

        return response()->json(['data' => $this->planData($plan)]);
    }

    public function addCommitment(Request $request, string $planId, IdempotencyKeyService $idempotency): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'activityId' => ['required', 'ulid'],
        ]);
        $user = $request->user('app');
        $idempotencyKey = $request->header('Idempotency-Key');
        abort_unless(is_string($idempotencyKey) && strlen($idempotencyKey) >= 16 && strlen($idempotencyKey) <= 128, 422);
        $planContext = Plan::query()
            ->whereKey($planId)
            ->where('owner_user_id', $user->id)
            ->firstOrFail();
        Gate::forUser($user)->authorize('view', $planContext);
        $idempotencyPayload = ['planId' => $planId, 'request' => $data];

        try {
            $execution = $idempotency->run(
                $user,
                $planContext->organization_id,
                'plan.commitment.add',
                $idempotencyKey,
                $idempotencyPayload,
                function () use ($data, $planId, $user): array {
                    return DB::transaction(function () use ($data, $planId, $user): array {
                        $plan = Plan::query()
                            ->whereKey($planId)
                            ->where('owner_user_id', $user->id)
                            ->lockForUpdate()
                            ->firstOrFail();
                        Gate::forUser($user)->authorize('view', $plan);

                        if ($plan->status !== 'active') {
                            return ['closed' => true];
                        }
                        if ($plan->lock_version !== $data['expectedVersion']) {
                            return ['conflict' => $plan->lock_version];
                        }
                        if ($plan->focus_theme !== 'develop') {
                            return ['invalid' => true];
                        }

                        $activity = Activity::query()
                            ->visibleTo($user, $plan->organization_id)
                            ->with('currentVersion')
                            ->whereKey($data['activityId'])
                            ->first();
                        if (! $activity || $activity->kind !== 'custom' || $activity->currentVersion->theme !== 'develop') {
                            return ['invalid' => true];
                        }

                        $existing = PlanCommitment::query()
                            ->where('plan_id', $plan->id)
                            ->where('activity_version_id', $activity->current_version_id)
                            ->first();
                        if ($existing) {
                            return ['duplicate' => true];
                        }

                        $commitment = PlanCommitment::create([
                            'organization_id' => $plan->organization_id,
                            'plan_id' => $plan->id,
                            'activity_version_id' => $activity->current_version_id,
                            'pinned_theme' => 'develop',
                            'pinned_scope' => $activity->currentVersion->scope,
                            'display_order' => (int) $plan->commitments()->max('display_order') + 1,
                            'status' => 'active',
                            'created_at' => now(),
                        ]);
                        $plan->lock_version++;
                        $plan->save();

                        return ['commitment' => $commitment];
                    });
                },
                fn (array $result): ?string => $result['commitment']->id ?? null,
            );
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'plan_commitments_plan_id_display_order_unique')) {
                return response()->json(['error' => ['code' => 'commitment_conflict', 'message' => 'The plan changed while the activity was being added.']], 409);
            }
            throw $exception;
        }

        if (in_array($execution['state'], ['key_reused', 'in_progress'], true)) {
            return response()->json(['error' => [
                'code' => $execution['state'] === 'key_reused' ? 'idempotency_key_reused' : 'idempotency_in_progress',
                'message' => 'The idempotency key cannot be used for this request.',
            ]], 409);
        }
        $commitmentId = $execution['state'] === 'replay'
            ? $execution['reference']
            : ($execution['result']['commitment']->id ?? null);
        if (isset($execution['result']['conflict'])) {
            return response()->json(['error' => [
                'code' => 'version_conflict',
                'message' => 'This plan changed since it was loaded.',
                'currentVersion' => $execution['result']['conflict'],
            ]], 409);
        }
        if (($execution['result']['closed'] ?? false) === true) {
            return response()->json(['error' => ['code' => 'plan_closed', 'message' => 'A closed plan cannot be changed.']], 409);
        }
        if (($execution['result']['invalid'] ?? false) === true) {
            return response()->json(['error' => ['code' => 'custom_develop_activity_required', 'message' => 'Only your available custom Develop activities can be added to a Develop plan.']], 422);
        }
        if (($execution['result']['duplicate'] ?? false) === true) {
            return response()->json(['error' => ['code' => 'commitment_exists', 'message' => 'This activity is already in the plan.']], 409);
        }

        $commitment = PlanCommitment::query()
            ->whereKey($commitmentId)
            ->where('plan_id', $planId)
            ->with('activityVersion')
            ->firstOrFail();

        return response()->json(['data' => [
            'id' => $commitment->id,
            'activityVersionId' => $commitment->activity_version_id,
            'theme' => $commitment->pinned_theme,
            'scope' => $commitment->pinned_scope,
        ]], $execution['state'] === 'replay' ? 200 : 201);
    }

    private function authorizedOrganization(User $user, string $organizationId): Organization
    {
        return Organization::query()
            ->whereKey($organizationId)
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('organization_memberships')
                ->whereColumn('organization_memberships.organization_id', 'organizations.id')
                ->where('organization_memberships.user_id', $user->id)
                ->where('organization_memberships.status', 'active')
                ->whereNull('organization_memberships.revoked_at'))
            ->firstOrFail();
    }

    private function planData(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'organizationId' => $plan->organization_id,
            'month' => $plan->month->format('Y-m'),
            'timezone' => $plan->timezone,
            'title' => $plan->title,
            'focusTheme' => $plan->focus_theme,
            'status' => $plan->status,
            'workingRevision' => $plan->working_revision,
            'lockVersion' => $plan->lock_version,
            'metrics' => $this->metricsData($plan),
            'commitments' => $plan->commitments->map(fn (PlanCommitment $commitment): array => [
                'id' => $commitment->id,
                'activityVersionId' => $commitment->activity_version_id,
                'theme' => $commitment->pinned_theme,
                'scope' => $commitment->pinned_scope,
            ])->values(),
        ];
    }

    private function metricsData(Plan $plan): array
    {
        return app(PlanMetricsCalculator::class)->calculate(
            $plan->occurrences->map(fn ($occurrence): array => [
                'commitmentId' => $occurrence->commitment_id,
                'status' => $occurrence->status,
            ])->all(),
            $plan->commitments->map(fn (PlanCommitment $commitment): array => [
                'id' => $commitment->id,
                'scope' => $commitment->pinned_scope,
            ])->all(),
        );
    }
}
