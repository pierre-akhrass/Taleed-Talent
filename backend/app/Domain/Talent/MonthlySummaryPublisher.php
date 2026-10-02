<?php

namespace App\Domain\Talent;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

class MonthlySummaryPublisher
{
    /** @throws JsonException */
    public function publishLatestFor(Plan $plan): ?string
    {
        if (! config('talent.organization_sharing_enabled')) {
            return null;
        }

        return DB::transaction(function () use ($plan): ?string {
            $now = now();
            DB::table('sharing_periods')->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'organization_id' => $plan->organization_id,
                'month' => $plan->month->toDateString(),
                'next_version' => 1,
                'lock_version' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $period = DB::table('sharing_periods')
                ->where('organization_id', $plan->organization_id)
                ->where('month', $plan->month->toDateString())
                ->lockForUpdate()
                ->first();

            $latestRevisions = DB::table('plan_closures')
                ->select('plan_id', DB::raw('MAX(revision_number) as revision_number'))
                ->where('organization_id', $plan->organization_id)
                ->groupBy('plan_id');
            $closures = DB::table('plan_closures')
                ->joinSub($latestRevisions, 'latest_closures', function ($join): void {
                    $join->on('plan_closures.plan_id', '=', 'latest_closures.plan_id')
                        ->on('plan_closures.revision_number', '=', 'latest_closures.revision_number');
                })
                ->join('plans', 'plans.id', '=', 'plan_closures.plan_id')
                ->where('plans.organization_id', $plan->organization_id)
                ->whereDate('plans.month', $plan->month->toDateString())
                ->select([
                    'plan_closures.id',
                    'plan_closures.plan_id',
                    'plan_closures.owner_user_id',
                    'plan_closures.public_integrity_hash',
                    'plan_closures.safe_facts_json',
                    'plans.focus_theme',
                ])
                ->orderBy('plan_closures.plan_id')
                ->get();

            if ($closures->isEmpty()) {
                return null;
            }

            $activityCounts = array_fill_keys(['care', 'develop', 'enable', 'recognition'], 0);
            $scopeCounts = array_fill_keys(['individual', 'culture', 'team'], 0);
            foreach ($closures as $closure) {
                $facts = json_decode($closure->safe_facts_json, true, 512, JSON_THROW_ON_ERROR);
                foreach ($activityCounts as $theme => $_) {
                    $activityCounts[$theme] += (int) ($facts['activityCounts'][$theme] ?? 0);
                }
                foreach ($scopeCounts as $scope => $_) {
                    $scopeCounts[$scope] += (int) ($facts['scopeCounts'][$scope] ?? 0);
                }
            }

            $scheduled = 0;
            $completed = 0;
            $blocked = 0;
            $inProgress = 0;
            $cancelled = 0;
            $eligible = 0;
            $coverage = [];
            $fullyCoveredDevelopPlans = 0;
            foreach ($closures as $closure) {
                $facts = json_decode($closure->safe_facts_json, true, 512, JSON_THROW_ON_ERROR);
                $scheduled += (int) ($facts['scheduled'] ?? 0);
                $completed += (int) ($facts['completed'] ?? 0);
                $blocked += (int) ($facts['blocked'] ?? 0);
                $inProgress += (int) ($facts['inProgress'] ?? 0);
                $cancelled += (int) ($facts['cancelled'] ?? 0);
                $eligible += (int) ($facts['eligible'] ?? 0);
                $coverage = array_values(array_unique([...$coverage, ...($facts['coverage'] ?? [])]));
                if (($facts['focusTheme'] ?? null) === 'develop' && count(array_unique($facts['coverage'] ?? [])) === 3) {
                    $fullyCoveredDevelopPlans++;
                }
            }

            $version = (int) $period->next_version;
            $sharedAt = now()->toIso8601String();
            $payload = [
                'organizationId' => $plan->organization_id,
                'organizationName' => DB::table('organizations')->where('id', $plan->organization_id)->value('name'),
                'month' => $plan->month->format('Y-m'),
                'participatingLeaders' => $closures->pluck('owner_user_id')->unique()->count(),
                'closedPlans' => $closures->count(),
                'activityCounts' => $activityCounts,
                'scopeCounts' => $scopeCounts,
                'scheduled' => $scheduled,
                'completed' => $completed,
                'blocked' => $blocked,
                'inProgress' => $inProgress,
                'cancelled' => $cancelled,
                'eligible' => $eligible,
                'coverage' => $coverage,
                'fullyCoveredDevelopmentPlans' => $fullyCoveredDevelopPlans,
                'version' => $version,
                'sharedAt' => $sharedAt,
            ];
            $fingerprint = hash('sha256', json_encode($closures->map(fn ($closure): array => [
                'closureId' => $closure->id,
                'integrityHash' => $closure->public_integrity_hash,
            ])->all(), JSON_THROW_ON_ERROR));

            if ($period->current_summary_id) {
                $current = DB::table('shared_summaries')->where('id', $period->current_summary_id)->first();
                if ($current && hash_equals($current->source_revision_fingerprint, $fingerprint)) {
                    return $current->id;
                }
            }

            $summaryId = (string) Str::ulid();
            DB::table('shared_summaries')->insert([
                'id' => $summaryId,
                'organization_id' => $plan->organization_id,
                'sharing_period_id' => $period->id,
                'version_number' => $version,
                'source_revision_fingerprint' => $fingerprint,
                'approved_payload_json' => json_encode($payload, JSON_THROW_ON_ERROR),
                'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                'shared_at' => now(),
                'status' => 'effective',
            ]);
            foreach ($closures as $closure) {
                DB::table('shared_summary_sources')->insert([
                    'summary_id' => $summaryId,
                    'plan_closure_id' => $closure->id,
                ]);
            }
            if ($period->current_summary_id) {
                DB::table('shared_summaries')->where('id', $period->current_summary_id)->update(['status' => 'superseded']);
            }
            DB::table('sharing_periods')->where('id', $period->id)->update([
                'current_summary_id' => $summaryId,
                'next_version' => $version + 1,
                'lock_version' => $period->lock_version + 1,
                'updated_at' => now(),
            ]);

            return $summaryId;
        });
    }
}
