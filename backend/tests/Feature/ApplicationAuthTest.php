<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformRoleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApplicationAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_a_csrf_token(): void
    {
        User::factory()->create([
            'email' => 'leader@example.com',
            'normalized_email' => 'leader@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/auth/login', [
            'email' => 'leader@example.com',
            'password' => 'password',
        ])->assertStatus(419);
    }

    public function test_public_registration_is_disabled_and_password_reset_is_available(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'leader@example.com',
            'normalized_email' => 'leader@example.com',
        ]);

        $this->get('/register')->assertNotFound();

        $this->postJson('/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    public function test_application_admin_login_requires_confirmed_mfa(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'normalized_email' => 'admin@example.com',
            'password' => 'password',
        ]);
        PlatformRoleAssignment::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'assigned_at' => now(),
        ]);

        $this->postJson('/auth/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertForbidden()->assertJsonPath('error.code', 'mfa_required');
    }

    public function test_application_login_and_profile_use_the_app_guard(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = User::factory()->create([
            'email' => 'leader@example.com',
            'normalized_email' => 'leader@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/auth/login', [
            'email' => 'LEADER@example.com',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.email', 'leader@example.com');

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->get('/cp')->assertRedirect();
    }

    public function test_only_application_admin_can_create_leader_invitations(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $admin = User::factory()->create();
        $organization = Organization::create(['name' => 'Synthetic Organization']);

        $this->actingAs($admin, 'app')
            ->postJson('/api/v1/admin/organizations/'.$organization->id.'/invitations', [
                'email' => 'leader@example.com',
            ])->assertForbidden();

        PlatformRoleAssignment::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'app')
            ->postJson('/api/v1/admin/organizations/'.$organization->id.'/invitations', [
                'email' => 'leader@example.com',
            ])->assertCreated();

        $response->assertJsonPath('data.targetRole', 'leader');
        $this->assertDatabaseHas('invitations', [
            'organization_id' => $organization->id,
            'invited_email' => 'leader@example.com',
            'target_role' => 'leader',
        ]);
    }

    public function test_invitation_requires_matching_verified_email_and_is_single_use(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $admin = User::factory()->create();
        PlatformRoleAssignment::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'assigned_at' => now(),
        ]);
        $organization = Organization::create(['name' => 'Synthetic Organization']);

        $invitation = $this->actingAs($admin, 'app')
            ->postJson('/api/v1/admin/organizations/'.$organization->id.'/invitations', [
                'email' => 'leader@example.com',
            ])->json('data');

        $unverified = User::factory()->unverified()->create([
            'email' => 'leader@example.com',
            'normalized_email' => 'leader@example.com',
        ]);

        $this->actingAs($unverified, 'app')
            ->postJson('/api/v1/invitations/accept', ['token' => $invitation['token']])
            ->assertForbidden();

        $unverified->forceFill(['email_verified_at' => now()])->save();
        $leader = $unverified;

        $this->actingAs($leader, 'app')
            ->postJson('/api/v1/invitations/accept', ['token' => $invitation['token']])
            ->assertOk()
            ->assertJsonPath('data.role', 'leader');

        $this->actingAs($leader, 'app')
            ->postJson('/api/v1/invitations/accept', ['token' => $invitation['token']])
            ->assertGone();
    }

    public function test_expired_invitation_cannot_be_consumed(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $admin = User::factory()->create();
        PlatformRoleAssignment::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'assigned_at' => now(),
        ]);
        $organization = Organization::create(['name' => 'Expired Invitation Organization']);

        $invitation = $this->actingAs($admin, 'app')
            ->postJson('/api/v1/admin/organizations/'.$organization->id.'/invitations', [
                'email' => 'expired@example.com',
                'expires_at' => now()->addMinute()->toIso8601String(),
            ])->json('data');

        \App\Models\Invitation::query()->whereKey($invitation['id'])->update(['expires_at' => now()->subMinute()]);
        $leader = User::factory()->create([
            'email' => 'expired@example.com',
            'normalized_email' => 'expired@example.com',
        ]);

        $this->actingAs($leader, 'app')
            ->postJson('/api/v1/invitations/accept', ['token' => $invitation['token']])
            ->assertGone();
    }
}