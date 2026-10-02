<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Services\ApplicationPasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('normalized_email', mb_strtolower(trim($credentials['email'])))
            ->first();

        if (! $user || $user->status !== 'active' || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if ($user->isApplicationAdmin() && ! $user->two_factor_confirmed_at) {
            return response()->json(['error' => [
                'code' => 'mfa_required',
                'message' => 'Multi-factor authentication must be configured before this account can sign in.',
            ]], 403);
        }

        Auth::guard('app')->login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json(['data' => $this->userData($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('app')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => null]);
    }

    public function forgotPassword(Request $request, ApplicationPasswordResetService $resets): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:254']]);
        $user = User::query()->where('normalized_email', mb_strtolower(trim($data['email'])))->first();

        if ($user) {
            $token = $resets->createToken($user);
            $user->sendPasswordResetNotification($token);
        }

        return response()->json(['data' => [
            'message' => 'If the account exists, a password reset message has been queued.',
        ]]);
    }

    public function resetPassword(Request $request, ApplicationPasswordResetService $resets): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $user = User::query()->where('normalized_email', mb_strtolower(trim($data['email'])))->firstOrFail();

        abort_unless($resets->isValid($user, $data['token']), 422, 'The reset token is invalid or expired.');

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'authentication_version' => $user->authentication_version + 1,
        ])->save();
        $resets->deleteToken($user);

        return response()->json(['data' => ['message' => 'Password updated.']]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userData($request->user('app'))]);
    }

    public function createOrganization(Request $request): JsonResponse
    {
        Gate::forUser($request->user('app'))->authorize('create', Organization::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $organization = Organization::create($data + ['timezone' => $data['timezone'] ?? 'Asia/Riyadh']);

        return response()->json(['data' => [
            'id' => $organization->id,
            'name' => $organization->name,
            'timezone' => $organization->timezone,
        ]], 201);
    }

    public function createInvitation(Request $request, Organization $organization): JsonResponse
    {
        Gate::forUser($request->user('app'))->authorize('createInvitation', [Invitation::class, $organization]);

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        $token = Str::random(64);

        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'invited_email' => mb_strtolower(trim($data['email'])),
            'target_role' => 'leader',
            'token_digest' => hash('sha256', $token),
            'expires_at' => $data['expires_at'] ?? now()->addDays(7),
            'created_by' => $request->user('app')->id,
        ]);

        return response()->json(['data' => [
            'id' => $invitation->id,
            'expiresAt' => $invitation->expires_at->toIso8601String(),
            'token' => $token,
            'targetRole' => $invitation->target_role,
        ]], 201);
    }

    public function acceptInvitation(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $user = $request->user('app');
        abort_unless($user->hasVerifiedEmail(), 403, 'Email verification is required.');

        $membership = DB::transaction(function () use ($data, $user) {
            $invitation = Invitation::query()
                ->where('token_digest', hash('sha256', $data['token']))
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($invitation->revoked_at || $invitation->accepted_at || $invitation->expires_at->isPast(), 410);
            abort_unless(hash_equals($invitation->invited_email, $user->normalized_email), 403);

            $membership = DB::table('organization_memberships')
                ->where('organization_id', $invitation->organization_id)
                ->where('user_id', $user->id)
                ->first();

            if ($membership) {
                DB::table('organization_memberships')
                    ->where('id', $membership->id)
                    ->update(['status' => 'active', 'revoked_at' => null, 'updated_at' => now()]);
            } else {
                DB::table('organization_memberships')->insert([
                    'id' => (string) Str::ulid(),
                    'organization_id' => $invitation->organization_id,
                    'user_id' => $user->id,
                    'role' => 'leader',
                    'status' => 'active',
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $invitation->forceFill([
                'accepted_by' => $user->id,
                'accepted_at' => now(),
            ])->save();

            return $invitation->organization;
        });

        return response()->json(['data' => [
            'organizationId' => $membership->id,
            'organizationName' => $membership->name,
            'role' => 'leader',
        ]]);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'displayName' => $user->display_name ?? $user->name,
            'emailVerified' => $user->hasVerifiedEmail(),
            'applicationAdmin' => $user->isApplicationAdmin(),
            'organizations' => $user->organizations()
                ->wherePivot('status', 'active')
                ->get(['organizations.id', 'organizations.name'])
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'role' => $organization->pivot->role,
                ])->values(),
        ];
    }
}