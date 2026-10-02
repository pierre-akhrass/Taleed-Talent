<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PlanDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlanDraft>
 */
class PlanDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'owner_user_id' => User::factory(),
            'month' => '2026-10-01',
            'selected_theme' => null,
            'draft_payload_json' => ['title' => '', 'selectedActivityIds' => []],
            'step' => 'theme',
            'lock_version' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PlanDraft $draft): void {
            $membershipExists = DB::table('organization_memberships')
                ->where('organization_id', $draft->organization_id)
                ->where('user_id', $draft->owner_user_id)
                ->exists();

            if (! $membershipExists) {
                DB::table('organization_memberships')->insert([
                    'id' => (string) Str::ulid(),
                    'organization_id' => $draft->organization_id,
                    'user_id' => $draft->owner_user_id,
                    'role' => 'leader',
                    'status' => 'active',
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
