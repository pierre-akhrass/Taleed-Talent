<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookmarkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['organizationId' => ['required', 'ulid']]);
        $user = $request->user('app');
        $this->authorizedOrganization($user, $data['organizationId']);
        $activities = Activity::query()
            ->visibleTo($user, $data['organizationId'])
            ->join('bookmarks', 'bookmarks.activity_id', '=', 'activities.id')
            ->where('bookmarks.user_id', $user->id)
            ->select('activities.*', 'bookmarks.created_at as bookmarked_at')
            ->with('currentVersion')
            ->orderByDesc('bookmarks.created_at')
            ->orderBy('activities.id')
            ->get();

        return response()->json(['data' => $activities->map(fn (Activity $activity): array => [
            ...$this->activityData($activity),
            'bookmarkedAt' => $activity->bookmarked_at,
        ])->values()]);
    }

    public function store(Request $request, string $activityId): JsonResponse
    {
        $data = $request->validate(['organizationId' => ['required', 'ulid']]);
        $user = $request->user('app');
        $this->authorizedOrganization($user, $data['organizationId']);
        $activity = Activity::query()
            ->visibleTo($user, $data['organizationId'])
            ->with('currentVersion')
            ->whereKey($activityId)
            ->firstOrFail();
        DB::table('bookmarks')->insertOrIgnore([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'created_at' => now(),
        ]);
        $activity->bookmarked_at = DB::table('bookmarks')
            ->where('user_id', $user->id)
            ->where('activity_id', $activity->id)
            ->value('created_at');

        return response()->json(['data' => [
            ...$this->activityData($activity),
            'bookmarkedAt' => $activity->bookmarked_at,
        ]]);
    }

    public function destroy(Request $request, string $activityId): JsonResponse
    {
        $data = $request->validate(['organizationId' => ['required', 'ulid']]);
        $user = $request->user('app');
        $this->authorizedOrganization($user, $data['organizationId']);
        $deleted = DB::table('bookmarks')
            ->where('user_id', $user->id)
            ->where('activity_id', $activityId)
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('activities')
                ->whereColumn('activities.id', 'bookmarks.activity_id')
                ->where(function ($visible) use ($data, $user): void {
                    $visible->where(function ($source): void {
                        $source->where('kind', 'source')->where('status', 'published');
                    })->orWhere(function ($custom) use ($data, $user): void {
                        $custom->where('kind', 'custom')
                            ->where('organization_id', $data['organizationId'])
                            ->where('owner_user_id', $user->id);
                    });
                }))
            ->delete();
        abort_unless($deleted === 1, 404);

        return response()->json(status: 204);
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
