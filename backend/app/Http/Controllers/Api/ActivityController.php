<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    private const THEMES = ['care', 'develop', 'enable', 'recognition'];

    private const SCOPES = ['individual', 'culture', 'team'];

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationId' => ['required', 'ulid'],
            'theme' => ['sometimes', Rule::in(self::THEMES)],
            'scope' => ['sometimes', Rule::in(self::SCOPES)],
            'cursor' => ['sometimes', 'string', 'max:512'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $user = $request->user('app');
        $this->authorizedOrganization($user, $data['organizationId']);
        $limit = $data['limit'] ?? 50;

        $activities = Activity::query()->visibleTo($user, $data['organizationId'])
            ->with('currentVersion')
            ->when(isset($data['theme']), fn ($query) => $query->whereHas('currentVersion', fn ($version) => $version->where('theme', $data['theme'])))
            ->when(isset($data['scope']), fn ($query) => $query->whereHas('currentVersion', fn ($version) => $version->where('scope', $data['scope'])))
            ->when(isset($data['cursor']), fn ($query) => $query->where('id', '>', $data['cursor']))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();
        $hasMore = $activities->count() > $limit;
        $activities = $activities->take($limit)->values();

        return response()->json([
            'data' => $activities->map(fn (Activity $activity): array => $this->activityData($activity))->values(),
            'meta' => ['nextCursor' => $hasMore ? $activities->last()->id : null],
        ]);
    }

    public function show(Request $request, string $activityId): JsonResponse
    {
        $data = $request->validate(['organizationId' => ['required', 'ulid']]);
        $this->authorizedOrganization($request->user('app'), $data['organizationId']);
        $activity = Activity::query()->visibleTo($request->user('app'), $data['organizationId'])
            ->with('currentVersion')
            ->whereKey($activityId)
            ->firstOrFail();

        return response()->json(['data' => $this->activityData($activity)]);
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

    private function activityData(Activity $activity): array
    {
        $version = $activity->currentVersion;

        return [
            'id' => $activity->id,
            'kind' => $activity->kind,
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
