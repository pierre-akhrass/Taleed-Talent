<?php

namespace App\Http\Controllers\Api;

use App\Domain\Talent\ScheduleExpander;
use App\Http\Controllers\Controller;
use App\Models\CommitmentScheduleVersion;
use App\Models\Occurrence;
use App\Models\OccurrencePrivatePayload;
use App\Models\Plan;
use App\Models\PlanCommitment;
use App\Models\User;
use App\Services\Talent\IdempotencyKeyService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use PDOException;

class CommitmentScheduleController extends Controller
{
    public function store(Request $request, string $commitmentId, ScheduleExpander $expander, IdempotencyKeyService $idempotency): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'cadence' => ['required', Rule::in(['daily', 'weekly', 'one_off'])],
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d'],
            'weekdays' => ['sometimes', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['required', 'integer', 'between:0,6', 'distinct'],
        ]);
        $user = $request->user('app');
        $idempotencyKey = $request->header('Idempotency-Key');
        abort_unless(is_string($idempotencyKey) && strlen($idempotencyKey) >= 16 && strlen($idempotencyKey) <= 128, 422);
        $commitmentContext = $this->ownedCommitmentQuery($user, $commitmentId)->firstOrFail();
        $organizationId = $commitmentContext->plan->organization_id;
        $idempotencyPayload = ['commitmentId' => $commitmentId, 'request' => $data];

        try {
            $execution = $idempotency->run(
                $user,
                $organizationId,
                'commitment.schedule.create',
                $idempotencyKey,
                $idempotencyPayload,
                function () use ($data, $commitmentId, $expander, $user): CommitmentScheduleVersion {
                    return DB::transaction(function () use ($data, $commitmentId, $expander, $user): CommitmentScheduleVersion {
                        $commitment = $this->ownedCommitmentQuery($user, $commitmentId)->firstOrFail();
                        $plan = Plan::query()->whereKey($commitment->plan_id)->lockForUpdate()->firstOrFail();
                        if ($plan->lock_version !== $data['expectedVersion']) {
                            throw new ScheduleVersionConflict($plan->lock_version);
                        }

                        $versionNumber = (int) $commitment->scheduleVersions()->max('version_number') + 1;
                        $activityVersion = $commitment->activityVersion()->firstOrFail();
                        $scheduleData = [
                            'cadence' => $data['cadence'],
                            'startDate' => $data['startDate'],
                            'endDate' => $data['endDate'],
                            'weekdays' => $data['weekdays'] ?? [],
                        ];
                        $dates = $expander->expand($scheduleData, $plan->month->format('Y-m'), $activityVersion->theme);
                        $scheduleVersion = CommitmentScheduleVersion::create([
                            'organization_id' => $plan->organization_id,
                            'commitment_id' => $commitment->id,
                            'version_number' => $versionNumber,
                            'cadence' => $data['cadence'],
                            'start_date' => $data['startDate'],
                            'end_date' => $data['endDate'],
                            'weekdays_json' => $data['cadence'] === 'weekly' ? array_values($data['weekdays'] ?? []) : null,
                            'effective_from' => $data['startDate'],
                            'created_by' => $user->id,
                            'created_at' => now(),
                        ]);

                        $existingOccurrences = $commitment->occurrences()->get()->keyBy(fn (Occurrence $occurrence): string => $occurrence->generation_date->format('Y-m-d'));
                        $desiredDates = array_fill_keys($dates, true);
                        foreach ($existingOccurrences as $generationDate => $existingOccurrence) {
                            if (! in_array($existingOccurrence->status, ['scheduled', 'in_progress', 'blocked'], true)
                                || $generationDate < $data['startDate']
                                || isset($desiredDates[$generationDate])) {
                                continue;
                            }

                            $oldStatus = $existingOccurrence->status;
                            $existingOccurrence->status = 'superseded';
                            $existingOccurrence->lock_version++;
                            $existingOccurrence->save();
                            DB::table('occurrence_events')->insert([
                                'organization_id' => $plan->organization_id,
                                'occurrence_id' => $existingOccurrence->id,
                                'actor_id' => $user->id,
                                'from_status' => $oldStatus,
                                'to_status' => 'superseded',
                                'event_type' => 'schedule_superseded',
                                'created_at' => now(),
                            ]);
                        }

                        foreach ($dates as $date) {
                            $existingOccurrence = $existingOccurrences->get($date);
                            if ($existingOccurrence && $existingOccurrence->status !== 'superseded') {
                                if (in_array($existingOccurrence->status, ['scheduled', 'in_progress', 'blocked'], true)
                                    && $existingOccurrence->generation_date->format('Y-m-d') >= $data['startDate']
                                    && $existingOccurrence->schedule_version_id !== $scheduleVersion->id) {
                                    $existingOccurrence->schedule_version_id = $scheduleVersion->id;
                                    $existingOccurrence->lock_version++;
                                    $existingOccurrence->save();
                                    DB::table('occurrence_events')->insert([
                                        'organization_id' => $plan->organization_id,
                                        'occurrence_id' => $existingOccurrence->id,
                                        'actor_id' => $user->id,
                                        'from_status' => $existingOccurrence->status,
                                        'to_status' => $existingOccurrence->status,
                                        'event_type' => 'schedule_version_applied',
                                        'created_at' => now(),
                                    ]);
                                }

                                continue;
                            }

                            if ($existingOccurrence) {
                                $existingOccurrence->status = 'scheduled';
                                $existingOccurrence->scheduled_date = $date;
                                $existingOccurrence->schedule_version_id = $scheduleVersion->id;
                                $existingOccurrence->lock_version++;
                                $existingOccurrence->save();
                                DB::table('occurrence_events')->insert([
                                    'organization_id' => $plan->organization_id,
                                    'occurrence_id' => $existingOccurrence->id,
                                    'actor_id' => $user->id,
                                    'from_status' => 'superseded',
                                    'to_status' => 'scheduled',
                                    'event_type' => 'schedule_regenerated',
                                    'created_at' => now(),
                                ]);

                                continue;
                            }

                            Occurrence::create([
                                'organization_id' => $plan->organization_id,
                                'plan_id' => $plan->id,
                                'commitment_id' => $commitment->id,
                                'owner_user_id' => $user->id,
                                'generation_date' => $date,
                                'scheduled_date' => $date,
                                'schedule_version_id' => $scheduleVersion->id,
                                'status' => 'scheduled',
                                'lock_version' => 0,
                            ]);
                        }

                        $plan->lock_version++;
                        $plan->save();

                        return $scheduleVersion;
                    });
                },
                fn (mixed $result): ?string => $result instanceof CommitmentScheduleVersion ? $result->id : null,
            );
        } catch (ScheduleVersionConflict $exception) {
            return response()->json([
                'error' => [
                    'code' => 'version_conflict',
                    'message' => 'This plan changed since it was loaded.',
                    'requestId' => $request->attributes->get('request_id', ''),
                    'currentVersion' => $exception->currentVersion,
                ],
            ], 409);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'error' => ['code' => 'invalid_schedule', 'message' => $exception->getMessage()],
            ], 422);
        } catch (QueryException|PDOException $exception) {
            if ($this->isActiveDateCollision($exception)) {
                return response()->json([
                    'error' => ['code' => 'occurrence_date_collision', 'message' => 'Two active occurrences cannot share a commitment date.'],
                ], 409);
            }
            throw $exception;
        }

        if (in_array($execution['state'], ['key_reused', 'in_progress'], true)) {
            return response()->json(['error' => [
                'code' => $execution['state'] === 'key_reused' ? 'idempotency_key_reused' : 'idempotency_in_progress',
                'message' => 'The idempotency key cannot be used for this request.',
            ]], 409);
        }
        $schedule = $execution['state'] === 'replay'
            ? CommitmentScheduleVersion::query()->whereKey($execution['reference'])->where('commitment_id', $commitmentId)->firstOrFail()
            : $execution['result'];

        return response()->json(['data' => [
            'id' => $schedule->id,
            'commitmentId' => $schedule->commitment_id,
            'version' => $schedule->version_number,
            'cadence' => $schedule->cadence,
            'startDate' => $schedule->start_date->format('Y-m-d'),
            'endDate' => $schedule->end_date->format('Y-m-d'),
            'weekdays' => $schedule->weekdays_json ?? [],
        ]], 201);
    }

    public function calendar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organizationId' => ['required', 'ulid'],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);
        $user = $request->user('app');
        $this->authorizeOrganization($user, $data['organizationId']);
        $start = $data['month'].'-01';
        $end = date('Y-m-t', strtotime($start));
        $occurrences = Occurrence::query()
            ->where('organization_id', $data['organizationId'])
            ->where('owner_user_id', $user->id)
            ->where('status', '!=', 'superseded')
            ->whereBetween('scheduled_date', [$start, $end])
            ->with(['commitment.activityVersion', 'privatePayload'])
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $occurrences->map(fn (Occurrence $occurrence): array => [
            'id' => $occurrence->id,
            'planId' => $occurrence->plan_id,
            'commitmentId' => $occurrence->commitment_id,
            'generationDate' => $occurrence->generation_date->format('Y-m-d'),
            'scheduledDate' => $occurrence->scheduled_date->format('Y-m-d'),
            'status' => $occurrence->status,
            'lockVersion' => $occurrence->lock_version,
            'note' => $this->privateNote($occurrence),
            'theme' => $occurrence->commitment->pinned_theme,
            'scope' => $occurrence->commitment->pinned_scope,
        ])->values()]);
    }

    public function transition(Request $request, string $occurrenceId): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'blocked', 'completed', 'cancelled'])],
        ]);
        $user = $request->user('app');
        $result = DB::transaction(function () use ($data, $occurrenceId, $user): array {
            $initialOccurrence = $this->ownedOccurrenceQuery($user, $occurrenceId)->firstOrFail();
            $plan = Plan::query()->whereKey($initialOccurrence->plan_id)->lockForUpdate()->firstOrFail();
            if ($plan->status !== 'active') {
                return ['closed' => true];
            }
            $occurrence = $this->ownedOccurrenceQuery($user, $occurrenceId)->lockForUpdate()->firstOrFail();
            if ($occurrence->lock_version !== $data['expectedVersion']) {
                return ['conflict' => $occurrence->lock_version];
            }
            if ($occurrence->status === $data['status']) {
                return ['occurrence' => $occurrence];
            }
            if (! $this->canTransition($occurrence->status, $data['status'])) {
                return ['invalid' => true];
            }

            $oldStatus = $occurrence->status;
            $occurrence->status = $data['status'];
            $occurrence->completed_at = $data['status'] === 'completed' ? now() : null;
            $occurrence->lock_version++;
            $occurrence->save();
            $plan->lock_version++;
            $plan->save();
            DB::table('occurrence_events')->insert([
                'organization_id' => $occurrence->organization_id,
                'occurrence_id' => $occurrence->id,
                'actor_id' => $user->id,
                'from_status' => $oldStatus,
                'to_status' => $occurrence->status,
                'event_type' => 'status_changed',
                'created_at' => now(),
            ]);

            return ['occurrence' => $occurrence];
        });

        if (array_key_exists('conflict', $result)) {
            return $this->occurrenceConflict($request, $result['conflict']);
        }
        if (array_key_exists('invalid', $result)) {
            return response()->json(['error' => ['code' => 'invalid_transition', 'message' => 'This occurrence cannot move to the requested status.']], 422);
        }
        if (array_key_exists('closed', $result)) {
            return response()->json(['error' => ['code' => 'plan_closed', 'message' => 'A closed plan cannot be changed.']], 409);
        }

        return response()->json(['data' => $this->occurrenceData($result['occurrence'])]);
    }

    public function updateNote(Request $request, string $occurrenceId): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:4000'],
        ]);
        $user = $request->user('app');

        $result = DB::transaction(function () use ($data, $occurrenceId, $user): array {
            $initialOccurrence = $this->ownedOccurrenceQuery($user, $occurrenceId)->firstOrFail();
            $plan = Plan::query()->whereKey($initialOccurrence->plan_id)->lockForUpdate()->firstOrFail();
            if ($plan->status !== 'active') {
                return ['closed' => true];
            }
            $occurrence = $this->ownedOccurrenceQuery($user, $occurrenceId)->lockForUpdate()->firstOrFail();
            if ($occurrence->lock_version !== $data['expectedVersion']) {
                return ['conflict' => $occurrence->lock_version];
            }

            $note = trim((string) ($data['note'] ?? ''));
            $privatePayload = OccurrencePrivatePayload::query()->whereKey($occurrence->id)->lockForUpdate()->first();
            if ($note === '') {
                $privatePayload?->delete();
            } else {
                $privatePayload ??= new OccurrencePrivatePayload;
                $privatePayload->fill([
                    'occurrence_id' => $occurrence->id,
                    'organization_id' => $occurrence->organization_id,
                    'owner_user_id' => $user->id,
                    'encrypted_payload' => Crypt::encryptString($note),
                    'key_version' => (string) config('app.key_version'),
                    'lock_version' => ($privatePayload->lock_version ?? 0) + 1,
                ])->save();
            }

            $occurrence->lock_version++;
            $occurrence->save();
            $plan->lock_version++;
            $plan->save();
            DB::table('occurrence_events')->insert([
                'organization_id' => $occurrence->organization_id,
                'occurrence_id' => $occurrence->id,
                'actor_id' => $user->id,
                'event_type' => 'private_note_updated',
                'created_at' => now(),
            ]);

            return ['occurrence' => $occurrence->fresh('privatePayload')];
        });

        if (isset($result['conflict'])) {
            return $this->occurrenceConflict($request, $result['conflict']);
        }
        if (($result['closed'] ?? false) === true) {
            return response()->json(['error' => ['code' => 'plan_closed', 'message' => 'A closed plan cannot be changed.']], 409);
        }

        return response()->json(['data' => $this->occurrenceData($result['occurrence'])]);
    }

    public function reschedule(Request $request, string $occurrenceId, IdempotencyKeyService $idempotency): JsonResponse
    {
        $data = $request->validate([
            'expectedVersion' => ['required', 'integer', 'min:0'],
            'scheduledDate' => ['required', 'date_format:Y-m-d'],
        ]);
        $user = $request->user('app');
        $idempotencyKey = $request->header('Idempotency-Key');
        abort_unless(is_string($idempotencyKey) && strlen($idempotencyKey) >= 16 && strlen($idempotencyKey) <= 128, 422);
        $occurrenceContext = $this->ownedOccurrenceQuery($user, $occurrenceId)->firstOrFail();
        $idempotencyPayload = ['occurrenceId' => $occurrenceId, 'request' => $data];

        try {
            $execution = $idempotency->run(
                $user,
                $occurrenceContext->organization_id,
                'occurrence.reschedule',
                $idempotencyKey,
                $idempotencyPayload,
                fn (): array => DB::transaction(function () use ($data, $occurrenceId, $user): array {
                    $initialOccurrence = $this->ownedOccurrenceQuery($user, $occurrenceId)->firstOrFail();
                    $plan = Plan::query()->whereKey($initialOccurrence->plan_id)->lockForUpdate()->firstOrFail();
                    if ($plan->status !== 'active') {
                        return ['closed' => true];
                    }

                    $occurrence = $this->ownedOccurrenceQuery($user, $occurrenceId)->lockForUpdate()->firstOrFail();
                    if ($occurrence->lock_version !== $data['expectedVersion']) {
                        return ['conflict' => $occurrence->lock_version];
                    }
                    if (! in_array($occurrence->status, ['scheduled', 'in_progress', 'blocked'], true)) {
                        return ['invalid' => true];
                    }
                    if (! str_starts_with($data['scheduledDate'], $plan->month->format('Y-m'))) {
                        return ['invalid' => true];
                    }
                    if ($occurrence->scheduled_date->format('Y-m-d') !== $data['scheduledDate']
                        && Occurrence::query()
                            ->where('commitment_id', $occurrence->commitment_id)
                            ->where('id', '!=', $occurrence->id)
                            ->where('active_scheduled_date', $data['scheduledDate'])
                            ->exists()) {
                        return ['collision' => true];
                    }

                    $oldDate = $occurrence->scheduled_date->format('Y-m-d');
                    $occurrence->scheduled_date = $data['scheduledDate'];
                    $occurrence->lock_version++;
                    $occurrence->save();
                    $plan->lock_version++;
                    $plan->save();
                    DB::table('occurrence_events')->insert([
                        'organization_id' => $occurrence->organization_id,
                        'occurrence_id' => $occurrence->id,
                        'actor_id' => $user->id,
                        'old_date' => $oldDate,
                        'new_date' => $data['scheduledDate'],
                        'event_type' => 'rescheduled',
                        'created_at' => now(),
                    ]);

                    return ['occurrence' => $occurrence];
                }),
                fn (array $result): ?string => $result['occurrence']->id ?? null,
            );
        } catch (QueryException|PDOException $exception) {
            if ($this->isActiveDateCollision($exception)) {
                return response()->json(['error' => ['code' => 'occurrence_date_collision', 'message' => 'Two active occurrences cannot share a commitment date.']], 409);
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
            ? ['occurrence' => $this->ownedOccurrenceQuery($user, $occurrenceId)->firstOrFail()]
            : $execution['result'];

        if (array_key_exists('conflict', $result)) {
            return $this->occurrenceConflict($request, $result['conflict']);
        }
        if (array_key_exists('invalid', $result)) {
            return response()->json(['error' => ['code' => 'invalid_reschedule', 'message' => 'The occurrence cannot be rescheduled to that date.']], 422);
        }
        if (array_key_exists('closed', $result)) {
            return response()->json(['error' => ['code' => 'plan_closed', 'message' => 'A closed plan cannot be changed.']], 409);
        }
        if (array_key_exists('collision', $result)) {
            return response()->json(['error' => ['code' => 'occurrence_date_collision', 'message' => 'Two active occurrences cannot share a commitment date.']], 409);
        }

        return response()->json(['data' => $this->occurrenceData($result['occurrence'])]);
    }

    private function ownedCommitmentQuery(User $user, string $commitmentId)
    {
        return PlanCommitment::query()
            ->whereKey($commitmentId)
            ->whereHas('plan', fn ($query) => $query
                ->where('owner_user_id', $user->id)
                ->where('status', 'active')
                ->whereExists(fn ($membership) => $membership
                    ->selectRaw('1')
                    ->from('organization_memberships')
                    ->whereColumn('organization_memberships.organization_id', 'plans.organization_id')
                    ->where('organization_memberships.user_id', $user->id)
                    ->where('organization_memberships.status', 'active')
                    ->whereNull('organization_memberships.revoked_at')));
    }

    private function ownedOccurrenceQuery(User $user, string $occurrenceId)
    {
        return Occurrence::query()
            ->whereKey($occurrenceId)
            ->where('owner_user_id', $user->id)
            ->whereHas('plan', fn ($query) => $query
                ->where('owner_user_id', $user->id)
                ->whereHas('organization.users', fn ($membership) => $membership
                    ->where('users.id', $user->id)
                    ->where('organization_memberships.status', 'active')
                    ->whereNull('organization_memberships.revoked_at')));
    }

    private function authorizeOrganization(User $user, string $organizationId): void
    {
        abort_unless(DB::table('organization_memberships')
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->exists(), 404);
    }

    private function canTransition(string $from, string $to): bool
    {
        return in_array($from, [
            'scheduled' => ['scheduled', 'in_progress', 'blocked', 'completed', 'cancelled'],
            'in_progress' => ['in_progress', 'blocked', 'completed', 'cancelled'],
            'blocked' => ['blocked', 'scheduled', 'in_progress', 'completed', 'cancelled'],
        ][$from] ?? [], true);
    }

    private function occurrenceData(Occurrence $occurrence): array
    {
        return [
            'id' => $occurrence->id,
            'planId' => $occurrence->plan_id,
            'commitmentId' => $occurrence->commitment_id,
            'generationDate' => $occurrence->generation_date->format('Y-m-d'),
            'scheduledDate' => $occurrence->scheduled_date->format('Y-m-d'),
            'status' => $occurrence->status,
            'lockVersion' => $occurrence->lock_version,
            'note' => $this->privateNote($occurrence),
        ];
    }

    private function privateNote(Occurrence $occurrence): string
    {
        return $occurrence->privatePayload
            ? Crypt::decryptString($occurrence->privatePayload->encrypted_payload)
            : '';
    }

    private function occurrenceConflict(Request $request, int $currentVersion): JsonResponse
    {
        return response()->json(['error' => [
            'code' => 'version_conflict',
            'message' => 'This occurrence changed since it was loaded.',
            'requestId' => $request->attributes->get('request_id', ''),
            'currentVersion' => $currentVersion,
        ]], 409);
    }

    private function isActiveDateCollision(QueryException|PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), 'occurrences_commitment_id_active_scheduled_date_unique')
            || str_contains($exception->getMessage(), 'occurrences.active_scheduled_date');
    }
}

class ScheduleVersionConflict extends \RuntimeException
{
    public function __construct(public int $currentVersion)
    {
        parent::__construct('The plan version is stale.');
    }
}
