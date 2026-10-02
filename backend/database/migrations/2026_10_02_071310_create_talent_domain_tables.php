<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_analyst_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('source_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('stable_source_key', 100)->unique();
            $table->string('display_name');
            $table->string('source_kind', 32);
            $table->ulid('current_approved_version_id')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
        });

        Schema::create('source_document_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('source_document_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('storage_key', 512)->unique();
            $table->char('sha256', 64);
            $table->string('mime', 127);
            $table->unsignedBigInteger('byte_size');
            $table->string('rights_status', 32)->default('unverified');
            $table->string('dependency_status', 32)->default('unverified');
            $table->string('approval_status', 32)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['source_document_id', 'version_number'], 'source_versions_number_unique');
            $table->unique(['source_document_id', 'id']);
            $table->index(['approval_status', 'rights_status', 'dependency_status'], 'source_versions_approval_state_idx');
        });

        Schema::table('source_documents', function (Blueprint $table): void {
            $table->foreign(['id', 'current_approved_version_id'], 'source_documents_current_version_fk')
                ->references(['source_document_id', 'id'])
                ->on('source_document_versions')
                ->restrictOnDelete();
        });

        Schema::create('activities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('stable_activity_key', 120)->unique();
            $table->string('kind', 24);
            $table->foreignUlid('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->ulid('current_version_id')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'owner_user_id', 'status']);
        });

        Schema::create('activity_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('activity_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('theme', 24);
            $table->string('scope', 24);
            $table->text('original_text')->nullable();
            $table->string('adapted_title', 255);
            $table->text('description');
            $table->json('steps_json')->nullable();
            $table->foreignUlid('source_document_version_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('source_page', 64)->nullable();
            $table->text('adaptation_note')->nullable();
            $table->char('content_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['activity_id', 'version_number']);
            $table->unique(['activity_id', 'id']);
            $table->index(['theme', 'scope']);
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->foreign(['id', 'current_version_id'], 'activities_current_version_fk')
                ->references(['activity_id', 'id'])
                ->on('activity_versions')
                ->restrictOnDelete();
        });

        Schema::create('catalogue_publication_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('activity_version_id')->constrained()->restrictOnDelete();
            $table->string('action', 24);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('reason_code', 64)->nullable();
            $table->index(['activity_version_id', 'occurred_at'], 'publication_activity_date_idx');
        });

        Schema::create('bookmarks', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('activity_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'activity_id']);
        });

        Schema::create('plan_drafts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->date('month');
            $table->string('selected_theme', 24)->nullable();
            $table->json('draft_payload_json');
            $table->string('step', 40)->default('pick-theme');
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->foreign(['organization_id', 'owner_user_id'])
                ->references(['organization_id', 'user_id'])
                ->on('organization_memberships')
                ->restrictOnDelete();
            $table->index(['organization_id', 'owner_user_id', 'month']);
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->date('month');
            $table->string('timezone', 64)->default('Asia/Riyadh');
            $table->string('title', 255)->nullable();
            $table->string('focus_theme', 24);
            $table->string('status', 24)->default('active');
            $table->unsignedInteger('working_revision')->default(1);
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'id', 'owner_user_id']);
            $table->unique(['organization_id', 'owner_user_id', 'month']);
            $table->foreign(['organization_id', 'owner_user_id'])
                ->references(['organization_id', 'user_id'])
                ->on('organization_memberships')
                ->restrictOnDelete();
            $table->index(['organization_id', 'month', 'status']);
        });

        Schema::create('plan_commitments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('plan_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('activity_version_id')->constrained()->restrictOnDelete();
            $table->string('pinned_theme', 24);
            $table->string('pinned_scope', 24);
            $table->unsignedTinyInteger('display_order');
            $table->string('status', 24)->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'plan_id', 'id']);
            $table->unique(['plan_id', 'display_order']);
            $table->foreign(['organization_id', 'plan_id'])->references(['organization_id', 'id'])->on('plans')->restrictOnDelete();
            $table->index(['plan_id', 'status']);
        });

        Schema::create('commitment_schedule_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('commitment_id')->constrained('plan_commitments')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('cadence', 24);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('weekdays_json')->nullable();
            $table->date('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['commitment_id', 'version_number']);
            $table->unique(['organization_id', 'commitment_id', 'id'], 'schedule_tenant_parent_id_unique');
            $table->foreign(['organization_id', 'commitment_id'], 'schedule_tenant_parent_fk')
                ->references(['organization_id', 'id'])
                ->on('plan_commitments')
                ->restrictOnDelete();
        });

        Schema::create('occurrences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('plan_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('commitment_id')->constrained('plan_commitments')->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->date('generation_date');
            $table->date('scheduled_date');
            $table->foreignUlid('schedule_version_id')->constrained('commitment_schedule_versions')->restrictOnDelete();
            $table->string('status', 24)->default('scheduled');
            $table->date('active_scheduled_date')->nullable()->storedAs("CASE WHEN `status` NOT IN ('cancelled', 'superseded') THEN `scheduled_date` ELSE NULL END");
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['commitment_id', 'generation_date']);
            $table->unique(['commitment_id', 'active_scheduled_date']);
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'id', 'owner_user_id']);
            $table->foreign(['organization_id', 'plan_id'])->references(['organization_id', 'id'])->on('plans')->restrictOnDelete();
            $table->foreign(['organization_id', 'plan_id', 'owner_user_id'], 'occurrence_plan_owner_fk')
                ->references(['organization_id', 'id', 'owner_user_id'])
                ->on('plans')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'plan_id', 'commitment_id'])->references(['organization_id', 'plan_id', 'id'])->on('plan_commitments')->restrictOnDelete();
            $table->foreign(['organization_id', 'commitment_id', 'schedule_version_id'], 'occurrence_schedule_tenant_fk')
                ->references(['organization_id', 'commitment_id', 'id'])
                ->on('commitment_schedule_versions')
                ->restrictOnDelete();
            $table->index(['organization_id', 'plan_id', 'scheduled_date', 'status'], 'occurrence_calendar_idx');
        });

        Schema::create('occurrence_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('occurrence_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->date('old_date')->nullable();
            $table->date('new_date')->nullable();
            $table->string('event_type', 32);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign(['organization_id', 'occurrence_id'], 'occurrence_event_tenant_fk')
                ->references(['organization_id', 'id'])
                ->on('occurrences')
                ->restrictOnDelete();
            $table->index(['occurrence_id', 'created_at']);
        });

        Schema::create('occurrence_private_payloads', function (Blueprint $table): void {
            $table->foreignUlid('occurrence_id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->longText('encrypted_payload');
            $table->string('key_version', 64);
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamps();
            $table->foreign(['organization_id', 'occurrence_id', 'owner_user_id'], 'occurrence_private_owner_fk')
                ->references(['organization_id', 'id', 'owner_user_id'])
                ->on('occurrences')
                ->cascadeOnDelete();
        });

        Schema::create('plan_closures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('plan_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->timestamp('closed_at');
            $table->char('public_integrity_hash', 64);
            $table->json('safe_facts_json');
            $table->json('pinned_version_ids_json');
            $table->string('metrics_definition_version', 64);
            $table->unique(['plan_id', 'revision_number']);
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'id', 'owner_user_id']);
            $table->foreign(['organization_id', 'plan_id', 'owner_user_id'], 'closure_plan_owner_fk')
                ->references(['organization_id', 'id', 'owner_user_id'])
                ->on('plans')
                ->restrictOnDelete();
            $table->index(['organization_id', 'closed_at']);
        });

        Schema::create('plan_closure_private_payloads', function (Blueprint $table): void {
            $table->foreignUlid('closure_id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->longText('encrypted_snapshot');
            $table->string('key_version', 64);
            $table->timestamp('erased_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign(['organization_id', 'closure_id', 'owner_user_id'], 'closure_private_owner_fk')
                ->references(['organization_id', 'id', 'owner_user_id'])
                ->on('plan_closures')
                ->cascadeOnDelete();
        });

        Schema::create('conversations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->date('month');
            $table->string('status', 24)->default('draft');
            $table->string('guide_version_id', 64);
            $table->longText('encrypted_payload');
            $table->string('key_version', 64);
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamps();
            $table->foreign(['organization_id', 'owner_user_id'])->references(['organization_id', 'user_id'])->on('organization_memberships')->restrictOnDelete();
            $table->index(['organization_id', 'owner_user_id', 'month']);
        });

        Schema::create('wellbeing_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->date('month');
            $table->unsignedInteger('working_revision')->default(1);
            $table->string('status', 24)->default('draft');
            $table->string('rules_version_id', 64);
            $table->longText('encrypted_payload');
            $table->string('key_version', 64);
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamps();
            $table->foreign(['organization_id', 'owner_user_id'])->references(['organization_id', 'user_id'])->on('organization_memberships')->restrictOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'owner_user_id', 'month']);
        });

        Schema::create('wellbeing_revisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('wellbeing_entry_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->longText('encrypted_payload');
            $table->string('key_version', 64);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['wellbeing_entry_id', 'revision_number']);
            $table->foreign(['organization_id', 'wellbeing_entry_id'])->references(['organization_id', 'id'])->on('wellbeing_entries')->restrictOnDelete();
        });

        Schema::create('private_export_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('kind', 32);
            $table->string('source_reference', 128)->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('storage_key', 512)->nullable()->unique();
            $table->char('sha256', 64)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->foreign(['organization_id', 'owner_user_id'])->references(['organization_id', 'user_id'])->on('organization_memberships')->restrictOnDelete();
            $table->index(['owner_user_id', 'status', 'expires_at']);
        });

        Schema::create('deletion_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('scope', 32);
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('status', 24)->default('requested');
            $table->timestamps();
            $table->index(['user_id', 'status', 'requested_at']);
        });

        Schema::create('privacy_tombstones', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('subject_reference', 64)->unique();
            $table->string('scope', 32);
            $table->timestamp('effective_at');
            $table->timestamp('retention_until');
            $table->index(['scope', 'retention_until']);
        });

        Schema::create('sharing_periods', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->date('month');
            $table->ulid('current_summary_id')->nullable();
            $table->unsignedInteger('next_version')->default(1);
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'month']);
            $table->unique(['organization_id', 'id']);
        });

        Schema::create('shared_summaries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('sharing_period_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->char('source_revision_fingerprint', 64);
            $table->json('approved_payload_json');
            $table->char('payload_hash', 64);
            $table->timestamp('shared_at');
            $table->string('status', 24)->default('effective');
            $table->timestamp('withdrawn_at')->nullable();
            $table->unique(['sharing_period_id', 'version_number']);
            $table->unique(['organization_id', 'sharing_period_id', 'id']);
            $table->foreign(['organization_id', 'sharing_period_id'], 'summary_period_tenant_fk')
                ->references(['organization_id', 'id'])
                ->on('sharing_periods')
                ->restrictOnDelete();
            $table->index(['status', 'shared_at']);
        });

        Schema::table('sharing_periods', function (Blueprint $table): void {
            $table->foreign(['organization_id', 'id', 'current_summary_id'], 'sharing_periods_current_summary_fk')
                ->references(['organization_id', 'sharing_period_id', 'id'])
                ->on('shared_summaries')
                ->restrictOnDelete();
        });

        Schema::create('shared_summary_sources', function (Blueprint $table): void {
            $table->foreignUlid('summary_id')->constrained('shared_summaries')->cascadeOnDelete();
            $table->foreignUlid('plan_closure_id')->constrained('plan_closures')->restrictOnDelete();
            $table->primary(['summary_id', 'plan_closure_id']);
        });

        Schema::create('idempotency_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('organization_id')->constrained()->restrictOnDelete();
            $table->string('operation', 96);
            $table->char('key_digest', 64);
            $table->char('request_hash', 64);
            $table->string('result_reference', 128)->nullable();
            $table->string('status', 24)->default('processing');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->unique(['user_id', 'organization_id', 'operation', 'key_digest'], 'idempotency_scope_key_unique');
            $table->index(['expires_at', 'status']);
        });

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('aggregate_type', 64);
            $table->string('aggregate_id', 64);
            $table->string('event_type', 96);
            $table->string('deduplication_key', 191)->unique();
            $table->timestamp('available_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
            $table->index(['processed_at', 'available_at']);
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 96);
            $table->string('object_type', 64);
            $table->string('object_id', 64);
            $table->string('request_id', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->json('metadata_json')->nullable();
            $table->index(['organization_id', 'occurred_at']);
            $table->index(['object_type', 'object_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('idempotency_requests');
        Schema::dropIfExists('shared_summary_sources');
        Schema::table('sharing_periods', function (Blueprint $table): void {
            $table->dropForeign('sharing_periods_current_summary_fk');
        });
        Schema::dropIfExists('shared_summaries');
        Schema::dropIfExists('sharing_periods');
        Schema::dropIfExists('privacy_tombstones');
        Schema::dropIfExists('deletion_requests');
        Schema::dropIfExists('private_export_requests');
        Schema::dropIfExists('wellbeing_revisions');
        Schema::dropIfExists('wellbeing_entries');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('plan_closure_private_payloads');
        Schema::dropIfExists('plan_closures');
        Schema::dropIfExists('occurrence_private_payloads');
        Schema::dropIfExists('occurrence_events');
        Schema::dropIfExists('occurrences');
        Schema::dropIfExists('commitment_schedule_versions');
        Schema::dropIfExists('plan_commitments');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('plan_drafts');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('catalogue_publication_events');
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropForeign('activities_current_version_fk');
        });
        Schema::dropIfExists('activity_versions');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('source_document_versions');
        Schema::table('source_documents', function (Blueprint $table): void {
            $table->dropForeign('source_documents_current_version_fk');
        });
        Schema::dropIfExists('source_documents');
        Schema::dropIfExists('organization_analyst_assignments');
    }
};
