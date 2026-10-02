<?php

namespace App\Http\Controllers\Api;

use App\Domain\Talent\PickThreeValidator;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanCommitment;
use App\Models\PlanDraft;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanDraftController extends Controller
{
    private const THEMES = ['care', 'develop', 'enable', 'recognition'];

    private const SCOPES = ['individual', 'culture', 'team'];

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationId' => ['required', 'ulid'],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $this->authorizedOrganization($request->user('app'), $data['organizationId']);

        $drafts = PlanDraft::query()
            ->where('organization_id', $data['organizationId'])
            ->where('owner_user_id', $request->user('app')->id)
            ->whereDate('month', $data['month'].'-01')
            ->orderBy('id')
            ->limit($data['limit'] ?? 50)
            ->get();

        return response()->json(['data' => $drafts->map(fn (PlanDraft $draft): array => $this->draftData($draft))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationId' => ['required', 'ulid'],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'title' => ['nullable', 'string', 'max:100'],
            'selectedTheme' => ['nullable', Rule::in(self::THEMES)],
        ]);
        $user = $request->user('app');
        $organization = $this->authorizedOrganization($user, $data['organizationId']);

        $draft = PlanDraft::create([
            'organization_id' => $organization->id,
            'owner_user_id' => $user->id,
            'month' => $data['month'].'-01',
            'selected_theme' => $data['selectedTheme'] ?? null,
            'draft_payload_json' => [
                'title' => $data['title'] ?? '',
                'selectedActivityIds' => [],
            ],
            'step' => 'theme',
            'lock_version' => 0,
        ]);

        return response()->json(['data' => $this->draftData($draft)], 201);
    }

    public function show(Request $request, string $draftId): JsonResponse
    {
        $draft = $this->ownedDraft($request->user('app'), $draftId);

        return response()->json(['data' => $this->draftData($draft)]);
    }

    public function update(Request $request, string $draftId): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'selectedTheme' => ['sometimes', 'nullable', Rule::in(self::THEMES)],
            'selectedActivityIds' => ['sometimes', 'array', 'max:3'],
            'selectedActivityIds.*' => ['required', 'ulid', 'distinct'],
            'title' => ['sometimes', 'string', 'max:100'],
            'step' => ['sometimes', 'string', 'max:40', Rule::in(['theme', 'activities', 'schedule', 'review'])],
        ]);
        $user = $request->user('app');

        $result = DB::transaction(function () use ($data, $draftId, $user): array {
            $draft = $this->ownedDraftQuery($user, $draftId)->lockForUpdate()->firstOrFail();
            if ($draft->lock_version !== $data['expectedVersion']) {
                return ['conflict' => $draft->lock_version];
            }
            if ($draft->step === 'activated') {
                return ['conflict' => $draft->lock_version];
            }

            $payload = $draft->draft_payload_json;
            if (array_key_exists('title', $data)) {
                $payload['title'] = $data['title'];
            }
            if (array_key_exists('selectedActivityIds', $data)) {
                $this->assertActivitiesVisible($user, $draft->organization_id, $data['selectedActivityIds']);
                $payload['selectedActivityIds'] = array_values($data['selectedActivityIds']);
            }
            if (array_key_exists('selectedTheme', $data)) {
                $draft->selected_theme = $data['selectedTheme'];
            }
            if (array_key_exists('step', $data)) {
                $draft->step = $data['step'];
            }
            $draft->draft_payload_json = $payload;
            $draft->lock_version++;
            $draft->save();

            return ['draft' => $draft->fresh()];
        });

        if (array_key_exists('conflict', $result)) {
            return $this->versionConflict($result['conflict']);
        }

        return response()->json(['data' => $this->draftData($result['draft'])]);
    }

    public function activate(Request $request, string $draftId, PickThreeValidator $pickThree): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
        ]);
        $user = $request->user('app');

        try {
            $result = DB::transaction(function () use ($data, $draftId, $user, $pickThree): array {
                $draft = $this->ownedDraftQuery($user, $draftId)->lockForUpdate()->firstOrFail();
                if ($draft->step === 'activated') {
                    $planId = $draft->draft_payload_json['activatedPlanId'] ?? null;
                    $plan = $planId ? Plan::query()->where('owner_user_id', $user->id)->find($planId) : null;
                    abort_unless($plan, 409, 'The activated plan could not be resolved.');

                    return ['plan' => $plan->load('commitments.activityVersion')];
                }
                if ($draft->lock_version !== $data['expectedVersion']) {
                    return ['conflict' => $draft->lock_version];
                }

                $payload = $draft->draft_payload_json;
                $selectedIds = $payload['selectedActivityIds'] ?? [];
                if (count($selectedIds) !== 3 || count(array_unique($selectedIds)) !== 3 || ! in_array($draft->selected_theme, self::THEMES, true)) {
                    return ['invalid' => true];
                }

                $activities = Activity::query()->visibleTo($user, $draft->organization_id)
                    ->with('currentVersion')
                    ->whereIn('id', $selectedIds)
                    ->get()
                    ->keyBy('id');
                if ($activities->count() !== 3) {
                    return ['invalid' => true];
                }

                $selections = [];
                $versionsByScope = [];
                foreach ($selectedIds as $activityId) {
                    $activity = $activities->get($activityId);
                    $version = $activity?->currentVersion;
                    if (! $version) {
                        return ['invalid' => true];
                    }
                    $selections[] = [
                        'theme' => $version->theme,
                        'scope' => $version->scope,
                        'available' => $activity->kind === 'custom' || $version->approved_at !== null,
                    ];
                    $versionsByScope[$version->scope] = $version;
                }
                if (! $pickThree->passes($draft->selected_theme, $selections)) {
                    return ['invalid' => true];
                }

                $organization = Organization::query()->findOrFail($draft->organization_id);
                $plan = Plan::create([
                    'organization_id' => $organization->id,
                    'owner_user_id' => $user->id,
                    'month' => $draft->month->toDateString(),
                    'timezone' => $organization->timezone,
                    'title' => $payload['title'] ?? '',
                    'focus_theme' => $draft->selected_theme,
                    'status' => 'active',
                    'working_revision' => 1,
                    'lock_version' => 0,
                ]);

                foreach (self::SCOPES as $index => $scope) {
                    $version = $versionsByScope[$scope];
                    PlanCommitment::create([
                        'organization_id' => $organization->id,
                        'plan_id' => $plan->id,
                        'activity_version_id' => $version->id,
                        'pinned_theme' => $version->theme,
                        'pinned_scope' => $version->scope,
                        'display_order' => $index + 1,
                        'status' => 'active',
                        'created_at' => now(),
                    ]);
                }

                $payload['activatedPlanId'] = $plan->id;
                $draft->draft_payload_json = $payload;
                $draft->step = 'activated';
                $draft->lock_version++;
                $draft->save();

                return ['plan' => $plan->load('commitments.activityVersion')];
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'error' => [
                    'code' => 'plan_month_exists',
                    'message' => 'A plan already exists for this Leader and month.',
                ],
            ], 409);
        }

        if (array_key_exists('conflict', $result)) {
            return $this->versionConflict($result['conflict']);
        }
        if (array_key_exists('invalid', $result)) {
            return response()->json([
                'error' => [
                    'code' => 'pick_three_invalid',
                    'message' => 'Choose three distinct available activities, one per scope, in the selected theme.',
                ],
            ], 422);
        }

        return response()->json(['data' => $this->planData($result['plan'])], 201);
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

    private function ownedDraft(User $user, string $draftId): PlanDraft
    {
        return $this->ownedDraftQuery($user, $draftId)->firstOrFail();
    }

    private function ownedDraftQuery(User $user, string $draftId)
    {
        return PlanDraft::query()
            ->whereKey($draftId)
            ->where('owner_user_id', $user->id)
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('organization_memberships')
                ->whereColumn('organization_memberships.organization_id', 'plan_drafts.organization_id')
                ->where('organization_memberships.user_id', $user->id)
                ->where('organization_memberships.status', 'active')
                ->whereNull('organization_memberships.revoked_at'));
    }

    private function assertActivitiesVisible(User $user, string $organizationId, array $activityIds): void
    {
        $visibleCount = Activity::query()->visibleTo($user, $organizationId)
            ->whereIn('id', $activityIds)
            ->count();
        abort_unless($visibleCount === count($activityIds), 422, 'One or more activities are not available to this Leader.');
    }

    private function draftData(PlanDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'organizationId' => $draft->organization_id,
            'month' => $draft->month->format('Y-m'),
            'title' => $draft->draft_payload_json['title'] ?? '',
            'selectedTheme' => $draft->selected_theme,
            'selectedActivityIds' => $draft->draft_payload_json['selectedActivityIds'] ?? [],
            'step' => $draft->step,
            'lockVersion' => $draft->lock_version,
        ];
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
            'commitments' => $plan->commitments->map(fn (PlanCommitment $commitment): array => [
                'id' => $commitment->id,
                'activityVersionId' => $commitment->activity_version_id,
                'theme' => $commitment->pinned_theme,
                'scope' => $commitment->pinned_scope,
            ])->values(),
        ];
    }

    private function versionConflict(int $currentVersion): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'version_conflict',
                'message' => 'This record changed since it was loaded.',
                'requestId' => request()->attributes->get('request_id', ''),
                'currentVersion' => $currentVersion,
            ],
        ], 409);
    }
}
