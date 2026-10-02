<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomActivityController extends Controller
{
    private const SCOPES = ['individual', 'culture', 'team'];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationId' => ['required', 'ulid'],
            'scope' => ['required', Rule::in(self::SCOPES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:4000'],
            'steps' => ['sometimes', 'array', 'max:12'],
            'steps.*' => ['required', 'string', 'max:500'],
        ]);
        $user = $request->user('app');
        $organization = $this->authorizedOrganization($user, $data['organizationId']);

        $activity = DB::transaction(function () use ($data, $organization, $user): Activity {
            $activityId = (string) Str::ulid();
            $activity = Activity::create([
                'id' => $activityId,
                'stable_activity_key' => 'custom-'.$activityId,
                'kind' => 'custom',
                'organization_id' => $organization->id,
                'owner_user_id' => $user->id,
                'status' => 'published',
            ]);
            $version = $this->createVersion($activity, $user, $data, 1);
            $activity->current_version_id = $version->id;
            $activity->save();

            return $activity->fresh('currentVersion');
        });

        return response()->json(['data' => $this->activityData($activity)], 201);
    }

    public function update(Request $request, string $activityId): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:1'],
            'scope' => ['sometimes', Rule::in(self::SCOPES)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:4000'],
            'steps' => ['sometimes', 'array', 'max:12'],
            'steps.*' => ['required', 'string', 'max:500'],
        ]);
        $user = $request->user('app');

        $result = DB::transaction(function () use ($activityId, $data, $user): array {
            $activity = $this->ownedActivityQuery($user, $activityId)->lockForUpdate()->firstOrFail();
            $current = $activity->currentVersion;
            if ($current->version_number !== $data['expectedVersion']) {
                return ['conflict' => $current->version_number];
            }

            $versionData = [
                'scope' => $data['scope'] ?? $current->scope,
                'title' => $data['title'] ?? $current->adapted_title,
                'description' => $data['description'] ?? $current->description,
                'steps' => $data['steps'] ?? $current->steps_json ?? [],
            ];
            $version = $this->createVersion($activity, $user, $versionData, $current->version_number + 1);
            $activity->current_version_id = $version->id;
            $activity->save();

            return ['activity' => $activity->fresh('currentVersion')];
        });

        if (isset($result['conflict'])) {
            return response()->json(['error' => [
                'code' => 'version_conflict',
                'message' => 'This activity changed since it was loaded.',
                'currentVersion' => $result['conflict'],
            ]], 409);
        }

        return response()->json(['data' => $this->activityData($result['activity'])]);
    }

    public function retire(Request $request, string $activityId): JsonResponse
    {
        $data = $request->validate(['expectedVersion' => ['required', 'integer', 'min:1']]);
        $user = $request->user('app');

        $result = DB::transaction(function () use ($activityId, $data, $user): array {
            $activity = $this->ownedActivityQuery($user, $activityId)->lockForUpdate()->firstOrFail();
            if ($activity->currentVersion->version_number !== $data['expectedVersion']) {
                return ['conflict' => $activity->currentVersion->version_number];
            }

            if ($activity->status === 'retired') {
                return ['activity' => $activity];
            }

            $activity->status = 'retired';
            $activity->save();
            DB::table('catalogue_publication_events')->insert([
                'activity_version_id' => $activity->current_version_id,
                'action' => 'retired',
                'actor_id' => $user->id,
                'occurred_at' => now(),
                'reason_code' => 'owner_retired_custom',
            ]);

            return ['activity' => $activity];
        });

        if (isset($result['conflict'])) {
            return response()->json(['error' => [
                'code' => 'version_conflict',
                'message' => 'This activity changed since it was loaded.',
                'currentVersion' => $result['conflict'],
            ]], 409);
        }

        return response()->json(['data' => ['id' => $result['activity']->id, 'status' => $result['activity']->status]]);
    }

    private function createVersion(Activity $activity, User $user, array $data, int $versionNumber): ActivityVersion
    {
        $content = [
            'theme' => 'develop',
            'scope' => $data['scope'],
            'title' => $data['title'],
            'description' => $data['description'],
            'steps' => array_values($data['steps'] ?? []),
        ];
        $version = ActivityVersion::create([
            'activity_id' => $activity->id,
            'version_number' => $versionNumber,
            'theme' => 'develop',
            'scope' => $content['scope'],
            'adapted_title' => $content['title'],
            'description' => $content['description'],
            'steps_json' => $content['steps'],
            'content_hash' => hash('sha256', json_encode($content, JSON_THROW_ON_ERROR)),
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return $version;
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

    private function ownedActivityQuery(User $user, string $activityId): Builder
    {
        return Activity::query()
            ->with('currentVersion')
            ->whereKey($activityId)
            ->where('kind', 'custom')
            ->where('owner_user_id', $user->id)
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('organization_memberships')
                ->whereColumn('organization_memberships.organization_id', 'activities.organization_id')
                ->where('organization_memberships.user_id', $user->id)
                ->where('organization_memberships.status', 'active')
                ->whereNull('organization_memberships.revoked_at'));
    }

    private function activityData(Activity $activity): array
    {
        $version = $activity->currentVersion;

        return [
            'id' => $activity->id,
            'kind' => $activity->kind,
            'status' => $activity->status,
            'currentVersion' => [
                'id' => $version->id,
                'version' => $version->version_number,
                'theme' => $version->theme,
                'scope' => $version->scope,
                'title' => $version->adapted_title,
                'description' => $version->description,
                'steps' => $version->steps_json ?? [],
            ],
        ];
    }
}
