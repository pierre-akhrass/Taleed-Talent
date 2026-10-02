<?php

namespace App\Services\Talent;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use JsonException;

class IdempotencyKeyService
{
    /** @throws JsonException */
    public function run(
        User $user,
        string $organizationId,
        string $operation,
        string $key,
        array $payload,
        Closure $callback,
        Closure $reference,
    ): array {
        $keyDigest = hash('sha256', $key);
        $requestHash = hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($callback, $keyDigest, $operation, $organizationId, $reference, $requestHash, $user): array {
            $now = now();
            $inserted = DB::table('idempotency_requests')->insertOrIgnore([
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'operation' => $operation,
                'key_digest' => $keyDigest,
                'request_hash' => $requestHash,
                'status' => 'processing',
                'expires_at' => $now->copy()->addDay(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $claim = DB::table('idempotency_requests')
                ->where('user_id', $user->id)
                ->where('organization_id', $organizationId)
                ->where('operation', $operation)
                ->where('key_digest', $keyDigest)
                ->lockForUpdate()
                ->first();

            if (! hash_equals($claim->request_hash, $requestHash)) {
                return ['state' => 'key_reused'];
            }
            if ($claim->status === 'completed' && $claim->expires_at > $now->toDateTimeString()) {
                return ['state' => 'replay', 'reference' => $claim->result_reference];
            }
            if ($inserted === 0 && $claim->status === 'processing' && $claim->expires_at > $now->toDateTimeString()) {
                return ['state' => 'in_progress'];
            }

            DB::table('idempotency_requests')->where('id', $claim->id)->update([
                'status' => 'processing',
                'result_reference' => null,
                'expires_at' => $now->copy()->addDay(),
                'updated_at' => $now,
            ]);

            $result = $callback();
            $resultReference = $reference($result);
            if (! is_string($resultReference) || $resultReference === '') {
                DB::table('idempotency_requests')->where('id', $claim->id)->delete();

                return ['state' => 'executed', 'result' => $result];
            }

            DB::table('idempotency_requests')->where('id', $claim->id)->update([
                'status' => 'completed',
                'result_reference' => $resultReference,
                'expires_at' => $now->copy()->addDay(),
                'updated_at' => now(),
            ]);

            return ['state' => 'executed', 'result' => $result, 'reference' => $resultReference];
        });
    }

    private function canonicalize(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->canonicalize($value);
            }
        }

        if (! array_is_list($payload)) {
            ksort($payload);
        }

        return $payload;
    }
}
