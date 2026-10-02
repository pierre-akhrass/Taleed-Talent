<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('normalized_email')->nullable()->after('email');
            $table->string('display_name')->nullable()->after('name');
            $table->string('status', 32)->default('active')->after('password');
            $table->unsignedBigInteger('authentication_version')->default(0)->after('status');
        });

        DB::table('users')->orderBy('id')->eachById(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'normalized_email' => mb_strtolower(trim($user->email)),
                'display_name' => $user->name,
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('normalized_email');
        });

        Schema::create('organizations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('sector')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('organization_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 32)->default('leader');
            $table->string('status', 32)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('platform_role_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 32);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'role', 'revoked_at']);
        });

        Schema::create('invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->string('invited_email');
            $table->string('target_role', 32)->default('leader');
            $table->string('token_digest', 128)->unique();
            $table->timestamp('expires_at');
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'invited_email', 'expires_at']);
        });

        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_start')->default(0);
            $table->string('locale', 16)->default('en');
            $table->boolean('reminders_opt_in')->default(false);
            $table->timestamps();
        });

        Schema::create('privacy_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('notice_version', 64);
            $table->string('purpose', 64);
            $table->timestamp('accepted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose', 'withdrawn_at']);
        });

        Schema::create('installation_identity', function (Blueprint $table): void {
            $table->id();
            $table->uuid('dataset_uuid')->unique();
            $table->string('environment_kind', 32);
            $table->timestamp('installed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_identity');
        Schema::dropIfExists('privacy_acceptances');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('platform_role_assignments');
        Schema::dropIfExists('organization_memberships');
        Schema::dropIfExists('organizations');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['normalized_email']);
            $table->dropColumn(['normalized_email', 'display_name', 'status', 'authentication_version']);
        });
    }
};