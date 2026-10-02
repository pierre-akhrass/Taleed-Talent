<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(config('talent.organization_sharing_enabled'), 404);
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'cursor' => ['sometimes', 'string', 'max:512'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);
        $user = $request->user('app');
        $assignedOrganizationIds = DB::table('organization_analyst_assignments')
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->select('organization_id');

        $summaries = DB::table('shared_summaries')
            ->join('sharing_periods', 'sharing_periods.id', '=', 'shared_summaries.sharing_period_id')
            ->whereIn('shared_summaries.organization_id', $assignedOrganizationIds)
            ->whereDate('sharing_periods.month', $data['month'].'-01')
            ->where('shared_summaries.status', 'effective')
            ->whereNull('shared_summaries.withdrawn_at')
            ->when(isset($data['cursor']), fn ($query) => $query->where('shared_summaries.id', '>', $data['cursor']))
            ->orderBy('shared_summaries.id')
            ->limit(($data['limit'] ?? 50) + 1)
            ->get([
                'shared_summaries.id',
                'shared_summaries.organization_id',
                'shared_summaries.approved_payload_json',
            ]);
        $hasMore = $summaries->count() > ($data['limit'] ?? 50);
        $summaries = $summaries->take($data['limit'] ?? 50)->values();

        return response()->json([
            'data' => $summaries->map(fn (object $summary): array => json_decode($summary->approved_payload_json, true, 512, JSON_THROW_ON_ERROR))->values(),
            'meta' => ['nextCursor' => $hasMore ? $summaries->last()->id : null],
        ]);
    }
}
